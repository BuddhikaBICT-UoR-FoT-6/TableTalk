<?php
namespace Models;

use Core\Database;
use PDO;

class Order {
    private $conn;
    private $table = 'orders';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function create($table_id, $total_amount, $estimated_wait_minutes, $notes = null) {
        $query = "INSERT INTO " . $this->table . " (table_id, total_amount, estimated_wait_minutes, notes) VALUES (:table_id, :total_amount, :estimated_wait_minutes, :notes)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':table_id', $table_id);
        $stmt->bindParam(':total_amount', $total_amount);
        $stmt->bindParam(':estimated_wait_minutes', $estimated_wait_minutes);
        $stmt->bindParam(':notes', $notes);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getActiveOrderCount() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE status IN ('pending', 'preparing')";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? (int)$row['count'] : 0;
    }

    public function findById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getActiveKitchenOrders() {
        $query = "SELECT * FROM " . $this->table . " WHERE status IN ('pending', 'preparing', 'ready') ORDER BY created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllOrders() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getActiveOrdersForTable($table_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE table_id = :table_id AND status IN ('pending', 'preparing', 'ready', 'served') ORDER BY created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':table_id', $table_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateStatus($id, $status, $estimated_wait_minutes) {
        $query = "UPDATE " . $this->table . " SET status = :status, estimated_wait_minutes = :estimated_wait_minutes WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':estimated_wait_minutes', $estimated_wait_minutes);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
