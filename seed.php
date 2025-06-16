<?php
require_once 'Core/Database.php';

use Core\Database;

try {
    $db = new Database();
    
    // Connect without DB name to create it if it doesn't exist
    $dsn = "mysql:host=127.0.0.1;charset=utf8mb4";
    $conn = new PDO($dsn, 'root', '1234');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL server successfully.\n";

    // Read the schema.sql file
    $sql = file_get_contents('schema.sql');
    
    if (!$sql) {
        die("Error: Could not read schema.sql file\n");
    }

    // Execute the SQL commands
    $conn->exec($sql);
    
    echo "Database seeded successfully!\n";
    
} catch (PDOException $e) {
    echo "Error seeding database: " . $e->getMessage() . "\n";
}
