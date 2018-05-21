<?php 
    $this_dir = str_replace('\\', '/', trailingslashit(__DIR__));
    
    // Custom inventory objects must be required here;
    require_once($this_dir . 'your-file-name.php');
?>