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

    public function create($name, $category, $price, $description, $rating, $image_url = null) {
        $query = "INSERT INTO " . $this->table . " (name, category, price, description, rating, image_url) VALUES (:name, :category, :price, :description, :rating, :image_url)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':image_url', $image_url);
        return $stmt->execute();
    }
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY category, name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function update($id, $name, $category, $price, $description, $rating, $image_url = null, $is_available = true) {
        $query = "UPDATE " . $this->table . " 
                  SET name = :name, category = :category, price = :price, 
                      description = :description, rating = :rating, is_available = :is_available";
        
        if ($image_url !== null) {
            $query .= ", image_url = :image_url";
        }
        $query .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':is_available', $is_available, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id);
        
        if ($image_url !== null) {
            $stmt->bindParam(':image_url', $image_url);
        }
        
        return $stmt->execute();
    }

    public function delete($id) {
        // Soft delete
        $query = "UPDATE " . $this->table . " SET is_available = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
