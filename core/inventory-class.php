<?php 
class Inventory {
    // public for other classes to reference
    // Stores request key to switch pages(like editing or inventory)
    static public $page_action_key = 'page-action';
    static public $edit_page_action = 'edit-item';

    // Key for storing ids in both $_GET and $_POST
    static public $ids_key = 'item-ids';
    // Stores request key that item actions will be sent through
    static public $item_action_key = 'action';
    static public $save_item_action = 'save-item';
    static public $delete_item_action = 'delete-item';
    static public $bulk_delete_action = 'delete_bulk_items';
    static public $new_item_id = 'new';

    // Private 
    public $page_slug;
    public $plural_name;
    public $db_table_name; 
    public $page_name;
    public $menu_btn_text;

    // These hold objects instantiated in constructor
    private $inventory_crud;
    private $main_inventory_page;
    private $edit_item_page;
    
    // Cleans or sets all needed values
    static private function clean_initial_str_vals($vals){
        global $wpdb;
        $singular_name = $vals['singular_name'];
        $cleaned_vals = [
            'db_table_name' => ($wpdb->prefix . $vals['db_table_name']),
            'singular_name' => $singular_name,
            'plural_name'   => (!empty($vals['plural_name']) ? $vals['plural_name'] : ($singular_name . 's') ), // TODO kind of hackey, right now it just sticks an 's' on the end
            'page_name'     => (!empty($vals['page_name']) ? $vals['page_name'] : (ucfirst($singular_name) . ' Inventory') ),    
            'page_slug'     => (!empty($vals['page_slug']) ? $vals['page_slug'] : ($singular_name . '-inventory') ), // TODO strip chars and replace spaces with underscores
            'menu_btn_text' => (!empty($vals['menu_btn_text']) ? $vals['menu_btn_text'] : ucfirst($singular_name) ), // TODO strip chars and replace spaces with underscores
        ];
        return $cleaned_vals;
    }

    public function __construct($initial_vals){
        if(empty($initial_vals['db_table_name']) || empty($initial_vals['singular_name']) || empty($initial_vals['inventory_columns'])){
            throw new Error('db_table_name, singular_name, and inventory_columns are all required in a custom inventory object');
            return false;
        }

        $str_vals = self::clean_initial_str_vals($initial_vals);
        $this->db_table_name    = $str_vals['db_table_name'];
        $this->singular_name    = $str_vals['singular_name'];
        $this->plural_name      = $str_vals['plural_name'];
        $this->page_name        = $str_vals['page_name'];
        $this->page_slug        = $str_vals['page_slug'];
        $this->menu_btn_text    = $str_vals['menu_btn_text'];

        $inventory_columns = $initial_vals['inventory_columns'];
        $bulk_actions = $initial_vals['bulk_actions'];

        // Initialize item-specific objects
        $inventory_crud             = new InventoryCRUD($str_vals, $inventory_columns, $bulk_actions);
        $this->inventory_crud       = $inventory_crud;
        $this->main_inventory_page  = new InventoryMainPage($str_vals, $inventory_columns, $bulk_actions, $inventory_crud);
        $this->edit_item_page    = new EditInventoryItemPage($str_vals, $inventory_columns, $inventory_crud);
    }

    public function install(){ 
        $this->inventory_crud->ensure_db_table_exists(); 
    }
    public function on_page_load(){ 
        return $this->inventory_crud->check_for_action_request();
    }

    public function add_page(){
        // Initializes WP_List_table child
        $this->main_inventory_page->install_table();

        add_menu_page(
            $this->page_name,
            $this->menu_btn_text, // Btn text for admin menu
            'activate_plugins',
            $this->page_slug,
            [
                $this, 
                'render_page'
            ]
        );
    }

    // Retrieves all items from DB in associative array
    public function get_all_items(){
        return $this->inventory_crud->get_all_items();
    }
    // Retrieves all item from DB based on it's id #
    public function get_item_by_id($item_id){
        return $this->inventory_crud->get_item_by_id($item_id);
    }

    // Renders inventory or editing page depending on $_GET value
    public function render_page(){
        $p_a_k = self::$page_action_key;
        $e_p_k = self::$edit_page_action;
        $i_k = self::$ids_key;
        if(!empty($_GET[$p_a_k]) && $_GET[$p_a_k] === $e_p_k && !empty($_GET[$i_k])){
            return $this->edit_item_page->render();
        }
        $this->main_inventory_page->render();
        self::render_admin_notices();
    }

    // Renders admin-notices based on $_GET request params
    static private function render_admin_notices(){
        if(empty($_GET['notice-action'])){ return false; }
        $result = $_GET['notice-result'];
        $action = $_GET['notice-action'];

        $notice_class;
        if($result === 'success'):
            $notice_class = 'success';
        elseif($result === 'fail'):
            $notice_class = 'error';
        else:
            $notice_class = 'warning';
        endif;
        // Holds message put into notice <p></p>
        $notice_message;

        // Invalid user
        if($action==='invalid-user' && $result === 'no-permission'):
            $notice_message = 'You do not have permission to change data.';

        // Save messages
        elseif(($action === 'save') && ($result === 'success')):
            $notice_message = 'Saved successfully';
        elseif($action === 'save' && $result === 'fail'):
            $notice_message = 'Save failed. If it keeps happening, contact your web developer.';
        elseif($action === 'save' && $result === 'not-updated'):
            $notice_message = "No changes saved.";
        elseif($action === 'save' && $result === 'empty-vals'):
            $notice_message = "Nothing to save. If you just refreshed the page, ignore this.";
        elseif($action === 'save' && $result === 'empty-id'):
            $notice_message = "Failed. Attempted to save under empty id.";
                
        // Delete messages
        elseif(($action === 'delete') && ($result === 'success')):
            $notice_message = 'Successfully deleted';
        elseif($action === 'delete' && $result === 'not-updated'):
            $notice_message = 'Nothing was deleted. It probably wasn\'t found';
        elseif($action === 'delete' && $result === 'empty-id'):
                $notice_message = 'No id to delete';
                
        elseif(($action === 'bulk-updates') && ($result === 'success')):
            $notice_message = 'Bulk updates successful';
        elseif(($action === 'bulk-updates') && ($result === 'fail')):
            $notice_message = 'Bulk updates failed';
        elseif(($action === 'bulk-updates') && ($result === 'not-updated')):
            $notice_message = 'Nothing updated';
        elseif(($action === 'bulk-updates') && ($result === 'empty-ids')):
            $notice_message = 'No ids selected';
            
            
        // Default message
        else:
            $notice_message = 'Something happened, but there\'s no message for it. Hopefully it was good.';
        endif;

        self::create_admin_notice($notice_class, $notice_message);
    }

    static public function create_admin_notice($notice_class, $notice_message){
        echo    "<div class='notice notice-$notice_class '>";
        echo        "<p>$notice_message</p>";
        echo    "</div> ";
    }
}
?>