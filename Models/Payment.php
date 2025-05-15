<?php
namespace Models;

use Core\Database;
use PDO;

class Payment {
    private $conn;
    private $table = 'payments';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function process($order_id, $amount, $method) {
        // Mock payment processing (simulate success)
        $query = "INSERT INTO " . $this->table . " (order_id, amount, method, status, paid_at) VALUES (:order_id, :amount, :method, 'completed', NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':method', $method);
        
        if ($stmt->execute()) {
            // Update order status to paid
            $orderQuery = "UPDATE orders SET status = 'paid' WHERE id = :order_id";
            $orderStmt = $this->conn->prepare($orderQuery);
            $orderStmt->bindParam(':order_id', $order_id);
            $orderStmt->execute();
            return true;
        }
        return false;
    }
}
