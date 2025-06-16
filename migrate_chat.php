<?php
require_once 'Core/Database.php';

use Core\Database;

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Add sender column to messages if it doesn't exist
    $sql = "ALTER TABLE messages ADD COLUMN sender ENUM('chef', 'table') DEFAULT 'chef' AFTER table_id";
    $conn->exec($sql);
    echo "Added sender column to messages.\n";

    // Add rating column to menu_items if it doesn't exist
    $sql2 = "ALTER TABLE menu_items ADD COLUMN rating DECIMAL(3,1) DEFAULT 4.5 AFTER price";
    $conn->exec($sql2);
    echo "Added rating column to menu_items.\n";

    // Seed some random ratings for existing items
    $conn->exec("UPDATE menu_items SET rating = 4.8 WHERE id = 1");
    $conn->exec("UPDATE menu_items SET rating = 4.2 WHERE id = 2");
    $conn->exec("UPDATE menu_items SET rating = 4.9 WHERE id = 3");
    $conn->exec("UPDATE menu_items SET rating = 4.5 WHERE id = 4");
    
    echo "Migration completed successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
