<?php 
class InventoryMainPage {
    private $db_table_name;
    private $page_slug;
    private $page_name;
    private $singular_name;

    private $inventory_crud;
    private $table_vals;
    private $inventory_table;
    
    // Gets arrays for WP_List_Table from 'inventory_columns'
    static private function get_column_options($inventory_columns, $dirty_bulk_actions){
        $column_headers = [];
        $sortable_columns = [];
        $hidden_columns = [];
        $columns_with_row_actions = [];
        $default_sort_col = '';
        $custom_column_funcs = [];
        $bulk_actions = []; 

        // Check for bulk_actions key
        if(!empty($dirty_bulk_actions)){
            foreach($dirty_bulk_actions as $action_type => $action_vals){
                if(!empty($action_vals['label'])){
                    $bulk_actions[$action_type] = $action_vals['label'];
                } else {
                    $bulk_actions[$action_type] = 'No \'label\' set';
                }   
            }
        }

        // Add delete items action
        $bulk_actions[Inventory::$bulk_delete_action] = "Delete items";

        // If there are bulk actions
        if(!empty($bulk_actions)){
            // Add checkbox column to table
            $column_headers['cb'] = '<input type="checkbox" />';
        }

        foreach($inventory_columns as $column_name => $column_options){
            $table_options = (!empty($column_options) && !empty($column_options['table'])) ?
                $column_options['table'] : false;
            if(!$table_options){ break; }
            
            // Adds column name in correct format as needed to WP_List_Table arrays
            // If 'display_as' is empty, this column isn't added to any arrays
            if(!empty($table_options['display_as'])){  
                $column_headers[$column_name] = $table_options['display_as']; 
                // Sortable
                if(!empty($table_options['sortable'])){  
                    $sortable_columns[$column_name] = [ $column_name, false ]; 
                }
                // Hidden
                if(!empty($table_options['hidden'])){  
                    $hidden_columns[] = $column_name; 
                }
                // Sort by this column by default
                if(!empty($table_options['default_sort'])){  $default_sort_col = $column_name; }
                // Add row actions
                if(!empty($table_options['display_row_actions'])){  
                    $columns_with_row_actions[] = $column_name; 
                }
                if(!empty($table_options['output_function'])){  $custom_column_funcs[$column_name] = $table_options['output_function']; }
            }   
        }

        // Builds table options variable
        return [
            'column_headers' => $column_headers,
            'sortable_columns' => $sortable_columns,
            'hidden_columns' => $hidden_columns,
            'columns_with_row_actions' => $columns_with_row_actions,
            'default_sort_column' => $default_sort_col,
            'custom_column_funcs' => $custom_column_funcs,
            'bulk_actions' => $bulk_actions
        ];
    }

    public function __construct($str_vals, $inventory_columns, $bulk_actions, $inventory_crud){
        $this->db_table_name = $str_vals['db_table_name'];
        $this->page_slug = $str_vals['page_slug'];
        $this->page_name = $str_vals['page_name'];

        // Saves instance of crud action object
        $this->inventory_crud = $inventory_crud;

        // Get option arrays for WP_List_Table
        // Has to be saved, then instantiated through a WP hook
        $this->table_vals = self::get_column_options($inventory_columns, $bulk_actions);
    }

    public function install_table(){
        // Assign new table object
        $this->inventory_table = new InventoryTable($this->table_vals);
    }

    private function render_header(){
        // Values for new item btn
        $page_action_key = Inventory::$page_action_key;
        $edit_item_action = Inventory::$edit_page_action;
        $id_key = Inventory::$ids_key;
        $new_id = Inventory::$new_item_id;
        echo "<h1 class='wp-heading-inline'>$this->page_name</h1>";
        echo "<a class='page-title-action' href='?page=$this->page_slug&$page_action_key=$edit_item_action&$id_key=$new_id'>Add New</a>";
        echo "<hr class='wp-header-end' />";
    }

    public function render(){
        $inventory_items = $this->inventory_crud->get_all_items();
        echo "<div class='wrap'>";
            $this->render_header();
            echo '<form method="POST">';
                $this->inventory_table->render_items($inventory_items);
            echo '</form>';
        echo "</div>";
    }

    
}

?>