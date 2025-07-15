<?php
require_once 'config.php';

try {
    // Drop tables in correct order due to foreign key constraints
    $db->exec("DROP TABLE IF EXISTS order_items");
    $db->exec("DROP TABLE IF EXISTS kue");
    
    echo "Tables dropped successfully. The config.php file will recreate them with correct image paths when you visit any page.";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>