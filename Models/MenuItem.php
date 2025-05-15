<?php
namespace Models;

use Core\Database;
use PDO;

class MenuItem {
    private $conn;
    private $table = 'menu_items';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAllAvailable() {
        $query = "SELECT * FROM " . $this->table . " WHERE is_available = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function findById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }
}
