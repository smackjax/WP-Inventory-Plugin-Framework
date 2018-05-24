<?php
class InventoryCRUD {
    // Holds database table name, already prefixed
    public  $db_table_name;
    private $inventory_columns;
    private $bulk_actions;
    private $page_slug;

    // Key for storing ids in both $_GET and $_POST is a static Inventory
    // Key for where item actions are passed is kept in Inventory
    
    public function __construct($str_vals, $inventory_columns, $bulk_actions){
        $this->db_table_name = $str_vals['db_table_name'];
        $this->inventory_columns = $inventory_columns;
        $this->page_slug = !empty($str_vals['page_slug']) ? 
            $str_vals['page_slug'] : ($str_vals['singular_name'] . '-inventory');
        $this->bulk_actions = $bulk_actions ?: [];
    }

    // Returns sql types for table creation
    static private function get_sql_data_type($data_format){
        if($data_format === '%d'){ return 'INTEGER'; }
        elseif($data_format === '%f'){ return 'FLOAT'; }
        // Default to string
        else { return 'VARCHAR(255)'; }
    }
    // Creates db table if it doesn't exist
    public function ensure_db_table_exists(){
        global $wpdb;
        $db_table_name = $this->db_table_name;
        $escapedName = $wpdb->esc_like($db_table_name);
        $query = $wpdb->prepare( "SHOW TABLES LIKE %s", $escapedName );
        // Check for table existence
        if ( $wpdb->get_var( $query ) == $db_table_name ) {
            return true;
        }
        // Create column for primary key
        $sql_columns = 'id SMALLINT(3) UNSIGNED NOT NULL AUTO_INCREMENT,';
        // Append column names and parameters(like data types) to query
        // Extract column names
        $new_cols = [];
        foreach($this->inventory_columns as $col_name => $col_vals){
            $data_types = !empty($col_vals['data']) ? $col_vals['data'] : [];
            $col_type; 
            // check if column data type explicitly set
            if(!empty($data_types['sql_type'])){
                $col_type = strtoupper($data_types['sql_type']);
            } else {
                // If no sql type given, try to use parse_type(default to string)
                $parse_type = !empty($data_types['parse_type']) ? $data_types['parse_type'] : '%s';
                $col_type = self::get_sql_data_type($parse_type);
            }
            $new_cols[] = " $col_name $col_type";
        }

        // Separate each table creation line with a comma
        $sql_columns .= implode(',', $new_cols);
        // Assign primary key, finish table columns
        $sql_columns .= ', PRIMARY KEY  (id)';
        // Get collation
        $db_collate = $wpdb->get_charset_collate();
        // Put it all together
        $SQL_create_table = "CREATE TABLE $db_table_name ($sql_columns) $db_collate";

        // Create table
        dbDelta($SQL_create_table);  

        // Check for table again
        if ( $wpdb->get_var( $query ) == $db_table_name ) {
            return true;
        }

        // Default to false(failure to find or create)
        return false;
    }

    // Checks for action requests and 
    // verifies if this class should handle them
    public function check_for_action_request(){
        // If wrong page for this handler, it does nothing
        if(empty($_GET['page']) || $_GET['page'] !== $this->page_slug){
            return false;
        }

        // Checks all action locations
        // Will return false if no action is set
        $action_type = null;
        if(!empty($_GET[Inventory::$item_action_key])): $action_type = $_GET[Inventory::$item_action_key];
        // Checks bulk actions
        elseif(!empty($_POST['action'])): $action_type = $_POST['action'];
        elseif(!empty($_POST['action2'])): $action_type =  $_POST['action2'];
        endif;

        // Check if page has an 'action' request
        if(!empty($action_type)){
            return $this->handle_action_request($action_type);    
        }
    }

    // Checks if page has bulk function, update, or delete
    private function handle_action_request($action_type){
        // Delete bulk items
        if($action_type === Inventory::$bulk_delete_action){
            $this->bulk_item_delete();
        }

        if(!empty($this->bulk_actions[$action_type])){
            // Update bulk 
            $current_bulk_action = $this->bulk_actions[$action_type];
            if( empty($current_bulk_action['db_values']) || !is_array($current_bulk_action['db_values']) ){
                throw new Error("$action_type bulk-action must have an array under 'db_values'. Array shape must be 'item_sql_col_name' => 'new_value'");
            }
            $this->bulk_item_update($current_bulk_action['db_values']);
            
        } else {
            if($action_type === Inventory::$save_item_action ){ 
                $this->save_item(); 
            }
            if($action_type === Inventory::$delete_item_action ){ 
                $this->delete_item(); 
            }
        }
    }

    // Removes any character that's not a number from string
    static public function only_nums($dirty_str){
        return (int)preg_replace('/[^0-9]+/', '', $dirty_str);
    }
    // Returns to base inventory, appends $_GET vals for admin notice
    private function redirect_with_notice($notice_action, $notice_result){
        $action_param = '&notice-action='.$notice_action;
        $result_param = '&notice-result='.$notice_result;
        $new_url = trailingslashit(admin_url()) . 'admin.php?page='. $this->page_slug . $action_param . $result_param;
        wp_safe_redirect($new_url);
        return true;
    }
    // Takes array of values to be emailed and logged to server
    private function email_and_log_error($error_vals){
        $error_email = $this->email_to_send_errors;
        
        // Email message
        @error_log(var_dump($error_vals), 1, $error_email);

        // Log to server
        @error_log('****** Custom Error log');
        foreach($error_vals as $key => $val){
            @error_log($key . '\n' . $val .'\n\n');
        }
        @error_log('***************\n');
        return true;
    }

    // Get's data parse format for column(%d, %f, %s)
    private function get_parse_format($col_name){
        // Get format type from inventory column(default string)
        $parse_format_type = $this->inventory_columns[$col_name]['data']['parse_type'];
        if(empty($parse_format_type)){ $parse_format_type = '%s'; }
        return $parse_format_type;
    }
    private function parse_new_value($parse_format_type, $new_col_val){
        // Parse value based on format type(default string)
        // Removes unneeded characters if int or float
        $parsed_val;
        if($parse_format_type === '%d'): $parsed_val = (int)self::only_nums($new_col_val);
        elseif($parse_format_type === '%f'): $parsed_val = floatval(preg_replace('/[^.0-9]+/', '', $new_col_val));
        // Encloses in quotes if string
        else: $parsed_val = stripslashes("$new_col_val");
        endif;

        return $parsed_val;
    }
    
    // ITEM CREATE/UPDATE
    private function save_item(){
        $item_id = ($_GET[inventory::$ids_key] === Inventory::$new_item_id) ? 
            Inventory::$new_item_id : 
            self::only_nums($_GET[inventory::$ids_key]);
        $table_name = $this->db_table_name;
        $notice_action = 'save';
        if(empty($item_id)){
            $this->redirect_with_notice($notice_action, 'empty-id');
            return false;
        }
        if(empty($_POST)) {
            $this->redirect_with_notice($notice_action, 'empty-vals');
            return false;
        }

        // Get item values for database
        $item_col_vals = [];
        $item_col_vals_format = [];
        // loop through post
        // Values don't need to be escaped here, they are run through $wpdb
        foreach($_POST as $col_name => $new_col_val){
            // Only get values for columns in DB
            if(empty($this->inventory_columns[$col_name])) return;
            // Gets format type(%d, %f, %s)
            $parse_format = $this->get_parse_format($col_name);
            // Parse value based on parse type
            $parsed_val = $this->parse_new_value($parse_format, $new_col_val);
            // Push value
            $item_col_vals[$col_name] = $parsed_val;
            // Push format
            $item_col_vals_format[$col_name] = $parse_format;
        }
        
        global $wpdb;
        // try/catch: If something goes catastrophically wrong, email me with a bug report
        try{
            $query_results;
            if( $item_id === Inventory::$new_item_id){
                $query_results = $wpdb->insert(
                    $table_name,
                    $item_col_vals,
                    $item_col_vals_format
                );
            } else {
                $query_results = $wpdb->update(
                    $table_name, 
                    $item_col_vals,
                    [ 'id'=>(int)$item_id ],
                    $item_col_vals_format,
                    ['id'=>'%d']
                 );
            }
            if( $query_results >= 1){
                $this->redirect_with_notice($notice_action, 'success');
            } else {
                $this->redirect_with_notice($notice_action, 'not-updated');
            }
        } catch( Error $e ){
            $this->email_and_log_error(
                [
                    'Site'=>'Here & Now',
                    'action'=>"Save beer values into $table_name",
                    'is_new_beer'=>($_GET[inventory::$ids_key] === Inventory::$new_item_id) ? 'Yes' : 'No',
                    'item_col_vals'=>var_dump($item_vals),
                    'error_message'=>$e->getMessage()
                ]
            );
            $this->redirect_with_notice($notice_action, 'fail');
            return false;
        }
    }

    private function delete_item(){
        $item_id = $_GET[inventory::$ids_key];
        $table_name = $this->db_table_name;
        $notice_action = 'delete';

        if(empty($item_id)){
            $this->redirect_with_notice($notice_action, 'empty-id');
            return false;
        }

        global $wpdb;
        $query_results;
        // try/catch: If something goes catastrophically wrong, email me with a bug report
        try{
            $query_results = $wpdb->delete(
                    $table_name,
                    array('id'=>$item_id)
                );
            if($query_results >= 1){
                $this->redirect_with_notice($notice_action, 'success');
            }
            else {
                $this->redirect_with_notice($notice_action, 'not-updated');
            }
        } catch( Error $e){
            $this->email_and_log_error(
                [
                    'Site'=>'Here & Now',
                    'action'=>"Delete item from $table_name",
                    'error_message'=>$e->getMessage()
                ]
            );
            $this->redirect_with_notice($notice_action, 'fail');
        }
    }
    
    // Functions for interaction with 'bulk-updates' on a table
    private function bulk_item_update($new_db_vals){
        $new_vals = $new_db_vals ?: [];
        if(empty($new_vals)){ return false; }

        $table_name = $this->db_table_name;
        $sql_cmd = "UPDATE $table_name SET";
        return $this->base_bulk_action($sql_cmd, $new_vals, false);
    }

    private function bulk_item_delete(){
        $table_name = $this->db_table_name;
        $sql_cmd = "DELETE FROM $table_name";
        return $this->base_bulk_action($sql_cmd, null, true);
    }

    // Gets array of ids in $_POST under specified key
    private function get_bulk_ids_from_post(){
        $id_key = Inventory::$ids_key;
        $posted_ids = !empty($_POST[$id_key]) ? $_POST[$id_key] : false;
        if(empty($posted_ids) ){ return false; }
        
        // Removes everything but numbers(since they can only be ids)
        $escaped_ids = array_map('InventoryCRUD::only_nums', $posted_ids);
        // Make into valid sql format
        $safe_ids = implode(',', $escaped_ids);
        return $safe_ids;
    }
   
    // IMPORTANT: $new_vals are not cleaned here. They must be escaped beforehand.
    // Executes query passed in with cleaned array of ids under array in $_POST.
    // Explicit $is_update bool argument keeps from deleting an item on accident
    // If deleting items, $new_vals MUST be empty
    private function base_bulk_action($sql_cmd, $new_vals = null, $is_delete){
        global $wpdb;
        $notice_action = 'bulk-updates';
        $notice_result = 'not-updated';
        $affected_rows = 0;

        try{
            $safe_ids = $this->get_bulk_ids_from_post();
            if(empty($safe_ids)){
                $notice_result = 'empty-ids';
                return self::redirect_with_notice($notice_action, $notice_result);
            }

            // * ** Updating
            if(!empty($new_vals) && !$is_delete){
                if(!is_array($new_vals)) { throw Error('Values to bulk update must be in array form'); }    
                /* * NOTE: Bear in mind this assigns values to columns directly. 
                If you want to assign a string, it has to be properly quoted within another string. */
                /* * Example: 
                $new_vals =[
                    on_tap => 1,
                    some_str => "'My string, yo'"
                ]; */
                $sql_vals = [];
                foreach($new_vals as $col_name => $new_val){
                    $parse_format_type = $this->get_parse_format($col_name);
                    $new_parsed_val = $this->parse_new_value($parse_format_type, $new_val);
                    // Surrounds strings in quotes for SQL
                    if($parse_format_type === '%s') $new_parsed_val = "'$new_parsed_val'";
                    $sql_vals[] = "$col_name=$new_parsed_val";
                }
                $formatted_sql_vals = implode(',', $sql_vals);

                // Put it all together
                $sql = "$sql_cmd $formatted_sql_vals WHERE id IN($safe_ids)";
                $affected_rows = $wpdb->query($sql);
            }

            // * ** Deleting
            if(empty($new_vals) && $is_delete){
                $sql = "$sql_cmd WHERE id IN($safe_ids)";
                $affected_rows = $wpdb->query($sql);
            }

            if($affected_rows > 0){ $notice_result = 'success'; }
            return self::redirect_with_notice($notice_action, $notice_result);
        } catch (Error $e){
            $notice_result = 'fail';
            return self::redirect_with_notice($notice_action, $notice_result);
        }
    }

    // ITEM RETRIEVAL
    public function get_all_items(){
        global $wpdb;
        $table_name = $this->db_table_name;
        return $wpdb->get_results(
            "SELECT * FROM $table_name",
            'ARRAY_A'
        );
    }
    public function get_item_by_id($item_id){
        global $wpdb;
        $table_name = $this->db_table_name;
        // Get item
        $query = "SELECT * FROM $table_name WHERE id=$item_id";
        $query_results = $wpdb->get_results(
            $query,
            'ARRAY_A'
        );
        $item =  !empty($query_results[0]) ? $query_results[0] : [];
        return $item;
    }
    public function get_items_by_column_value($col_name, $col_value){
        global $wpdb;
        $table_name = $this->db_table_name;
        $parse_format = $this->get_parse_format($col_name);
        $query_val = $this->parse_new_value($parse_format, $col_value);
        // Adds quotes around strings for SQL comparison
        if($parse_format === '%s') { $query_val = "'$query_val'"; }
        return $wpdb->get_results(
            "SELECT * FROM $table_name WHERE $col_name=$query_val",
            'ARRAY_A'
        );
    }
    public function get_items_by_where($where_condition){
        global $wpdb;
        $table_name = $this->db_table_name;
        return $wpdb->get_results(
            "SELECT * FROM $table_name WHERE $where_condition",
            'ARRAY_A'
        );
    }
}

?>