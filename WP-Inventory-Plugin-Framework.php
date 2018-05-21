<?php
    /*
    Plugin Name:  Inventory V2
    Description:  Handles custom inventory items
    Version:      1
    Author:       Max Bernard
    Author URI:   https://MaxBernard.Design
    License:      GPL2
    License URI:  https://www.gnu.org/licenses/gpl-2.0.html
    */

    // Don't allow direct access
    if(!defined('ABSPATH')){
        die('Cannot access this file directly');
    }

    // Paths
    $base_path = str_replace('\\', '/', trailingslashit(ABSPATH));
    $plugin_folder_path =  str_replace('\\', '/', trailingslashit(__DIR__));

    // Loads WP classes
    require_once( $base_path . 'wp-admin/includes/upgrade.php' );
    require_once( $base_path . 'wp-admin/includes/screen.php' );
    require_once( $base_path . 'wp-admin/includes/class-wp-screen.php' );
    require_once( $base_path . 'wp-admin/includes/class-wp-list-table.php' );
    require_once( $base_path . 'wp-admin/includes/template.php' );
    // NOTE this 'require' is just there in case WP_List_Table changes and the plugin needs to use this version
    // It cannot be just uncommented, it will cause problems with class redeclaration
    // require_once( $plugin_folder_path . '_wp-core/wp-list-table.php' );
    
    // Core plugin classes
    require_once($plugin_folder_path . 'core/_index.php');

    // Instantiate global $inventory
    $GLOBALS['inventory'] = new InventoryController();

    // $inventory holds references to all custom item objects
    global $inventory;
    // Load custom inventory
    require_once($plugin_folder_path . 'custom-inventory/_index.php');
    

    register_activation_hook(
        __FILE__,
        array(
            $inventory,
            'install'
        )
    );

    // Checks for crud operations
    add_action(
        'wp_loaded',
        [
            $inventory,
            'on_page_load'
        ]
    );
    
    // Instantiates WP_List_Table objects for each custom inventory
    // Add admin menu pages
    add_action( 
        'admin_menu',
        [
            $inventory,
            'add_pages'
        ]
    );

?>