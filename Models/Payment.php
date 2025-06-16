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
        try {
            $this->conn->beginTransaction();

            // Insert payment record
            $query = "INSERT INTO " . $this->table . " (order_id, amount, method, status, paid_at) VALUES (:order_id, :amount, :method, 'completed', NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':method', $method);
            $stmt->execute();
            
            // Update order status to paid
            $orderQuery = "UPDATE orders SET status = 'paid' WHERE id = :order_id";
            $orderStmt = $this->conn->prepare($orderQuery);
            $orderStmt->bindParam(':order_id', $order_id);
            $orderStmt->execute();

            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Payment Transaction Failed: " . $e->getMessage());
            return false;
        }
    }
}
