<?php 
class InventoryTable extends WP_List_Table{
    // Used by WP_List_table
    private $column_headers;
    private $sortable_columns;
    private $hidden_columns;
    
    private $columns_with_row_actions;
    private $table_bulk_actions;
    private $default_sort_column;
    private $table_data;

    private $custom_column_funcs;

    // Determines visible columns and behaviour
    function get_columns(){ return $this->column_headers; }
    function get_hidden_columns(){ return $this->hidden_columns; }
    function get_sortable_columns(){ return $this->sortable_columns; }
    function get_bulk_actions(){ return $this->table_bulk_actions; }

    function __construct($table_options){
        $this->column_headers =  $table_options['column_headers'] ?: [];
        $this->sortable_columns = $table_options['sortable_columns'] ?: [];
        $this->hidden_columns = $table_options['hidden_columns'] ?: [];

        $this->columns_with_row_actions = $table_options['columns_with_row_actions'] ?: [];
        $this->table_bulk_actions = $table_options['bulk_actions'] ?: [];

        $default_sort_column; 
        // Uses argument passed in for default sort column or first  selected column from headers
        if(!empty($table_options['default_sort_column'])){
            $default_sort_column = $table_options['default_sort_column'];
        } else {
            // TODO get column header from item
            foreach($table_options['column_headers'] as $col_title => $col_display_name){
                if(!empty($default_sort_column)){ break; }
                $default_sort_column = $col_title;
            }
        }
        $this->default_sort_column = $default_sort_column;

        // Anonymous functions that return column specific output
        $this->custom_column_funcs = $table_options['custom_column_funcs'] ?: [];

        // Call WP_List_Table constructor
        parent::__construct([
            'ajax' => false
        ]);
    }

    // Prepares and sets data for table
    // TODO make this an SQL query
    function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        usort($this->table_data, array($this, 'sort_default'));
        $this->items = $this->table_data;
    }   

    // Determines default HTML output behaviour in each column
    function column_default($item, $column_name) {
        $output = ''; 
        // Check for custom function to provide output
        if(!empty($this->custom_column_funcs[$column_name])){
            $custom_func = $this->custom_column_funcs[$column_name];
            $output = $custom_func($item, $column_name);
        }
        elseif(!empty($item[$column_name])){
            // If the item has data in the column name, 
            // populate with data
            $output = $item[$column_name];
        } 

        // Adds row actions edit | delete if col name is in array
        if(in_array($column_name, $this->columns_with_row_actions)){
            $actions = array(
                'edit' => sprintf('<a href="?page=%s&%s=%s&%s=%s">%s</a>',
                    $_GET['page'],
                    Inventory::$page_action_key,
                    Inventory::$edit_page_action,
                    Inventory::$ids_key,
                    $item['id'], 
                    'Edit'
                ),
                'delete' => sprintf('<a href="?page=%s&%s=%s&%s=%s">%s</a>', 
                    $_GET['page'], 
                    Inventory::$item_action_key, 
                    Inventory::$delete_item_action,
                    Inventory::$ids_key, 
                    $item['id'], 
                    'Delete'
                )
            );
            $output .= $this->row_actions($actions);
        }

        return $output;
    }

    // HTML Checkbox column
    function column_cb($item) {
        // Creates checkbox with 'value' set to item id
        return sprintf(
            '<input type="checkbox" name="%s[]" value="%s"/>',
            Inventory::$ids_key,
            $item['id']
        );
    }

    // Default sorting function
    function sort_default($a, $b) {
        // TODO load data with correct ORDERBY SQL
        // Default to on_tap
        $sort_column = $this->default_sort_column;
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : $sort_column;
        // If no sort order, default to descending
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'desc';
        // Sort items
        $result = strcmp($a[$orderby], $b[$orderby]);
        // Return final sort with appropriate direction
        return ($order === 'desc' ) ? -$result : $result;
    }

    function render_items($table_data = []){
        $this->table_data = $table_data;
        $this->prepare_items();
        $this->display();
    }
}
?>