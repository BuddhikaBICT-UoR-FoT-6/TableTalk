<?php
namespace Models;

use Core\Database;
use PDO;

class Feedback {
    private $conn;
    private $table = 'feedback';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function create($order_id, $rating, $comment) {
        $query = "INSERT INTO " . $this->table . " (order_id, rating, comment) VALUES (:order_id, :rating, :comment)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':comment', $comment);
        return $stmt->execute();
    }

    public function getAll() {
        $query = "SELECT f.*, o.table_id FROM " . $this->table . " f JOIN orders o ON f.order_id = o.id ORDER BY f.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getAggregateStats() {
        $query = "SELECT AVG(rating) as average_rating, COUNT(*) as total_reviews FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch();
    }
}
