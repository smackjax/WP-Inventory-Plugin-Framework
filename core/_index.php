<?php 
    $this_dir = str_replace('\\', '/', trailingslashit(__DIR__));
    // core classes
    require_once($this_dir . 'inventory-controller-class.php');
    require_once($this_dir . 'inventory-class.php');
    require_once($this_dir . 'inventory-crud-class.php');
    
    // Main table page
    require_once($this_dir . 'pages/inventory-main-page-class.php');
    require_once($this_dir . 'inventory-table-class.php');
    // Edit page
    require_once($this_dir . 'pages/edit-inventory-page-class.php');
?>