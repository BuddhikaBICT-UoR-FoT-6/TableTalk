<?php
require_once 'Core/Database.php';

use Core\Database;

try {
    $db = new Database();
    $conn = $db->connect();
    
    $hash = password_hash('password123', PASSWORD_BCRYPT);
    $sql = "UPDATE users SET password_hash = ? WHERE email = 'admin@tabletalk.local'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$hash]);
    
    echo "Admin password updated successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
