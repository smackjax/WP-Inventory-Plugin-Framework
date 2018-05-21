<?php
    class InventoryController {
        // Holds objects created with new Inventory()
        private $custom_inventory_objects = [];

        public function add_inventory_object($inventory_object){
            // If nothing in objects array yet, initialize it
            if(empty($this->custom_inventory_objects)){
                $this->custom_inventory_objects = [];
            }
            $inventory_name = $inventory_object->singular_name;
            if(empty($inventory_name)){
                throw Error("'singular_name' is empty on inventory object. \n " . var_dump($inventory_object));
            }
            $this->custom_inventory_objects[$inventory_name] = $inventory_object;
        }

        // ** PLUGIN ACTIVATION FUNCS
        // Ensures needed DB tables exist
        function install(){
            // Installs each sql table as needed
            foreach($this->custom_inventory_objects as $inventory_name => $inventory_object){
                $inventory_object->install();
            }
        }

        // Loads all inventory buttons and pages onto admin menu
        public function add_pages() {
            // Ensures user is logged in
            if(!is_user_logged_in()){
                auth_redirect();
                return false;
            }

            // Installs each sql table as needed
            foreach($this->custom_inventory_objects as $inventory_name => $inventory_object){
                $inventory_object->add_page();
            }            
        }

        // Checks each item for page requests to be handled by this inventory object
        public function on_page_load(){
            // Stops data handling if no user is logged in
            if(!is_user_logged_in()){ 
                return false; 
            }
            
            // Verify user permissions, redirect if not authorized
            if(!current_user_can('delete_posts')){
                Inventory::create_admin_notice('warning', 'This account does not have permission to edit inventory.');
            } else {
                // Handle requests
                foreach($this->custom_inventory_objects as $inventory_name => $inventory_object){
                    $inventory_object->on_page_load();
                }  
            }
        }
    }
?>