<?php
namespace Models;

use Core\Database;
use PDO;

class OrderItem {
    private $conn;
    private $table = 'order_items';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function create($order_id, $menu_item_id, $quantity, $subtotal) {
        $query = "INSERT INTO " . $this->table . " (order_id, menu_item_id, quantity, subtotal) VALUES (:order_id, :menu_item_id, :quantity, :subtotal)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':menu_item_id', $menu_item_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':subtotal', $subtotal);
        return $stmt->execute();
    }

    public function getByOrderId($order_id) {
        $query = "SELECT oi.*, m.name as item_name FROM " . $this->table . " oi JOIN menu_items m ON oi.menu_item_id = m.id WHERE oi.order_id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
