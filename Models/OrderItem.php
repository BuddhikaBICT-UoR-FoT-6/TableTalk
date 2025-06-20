<?php
namespace Models;

use Core\Database;
use PDO;

class OrderItem {
    private $conn;
    private $table = 'order_items';

    /**
     * OrderItem constructor.
     * Initializes the database connection.
     */
    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Creates a new order item entry.
     *
     * @param int $order_id The ID of the associated order.
     * @param int $menu_item_id The ID of the ordered menu item.
     * @param int $quantity The quantity ordered.
     * @param float $subtotal The subtotal amount for this item.
     * @return bool True on success, false on failure.
     */
    public function create($order_id, $menu_item_id, $quantity, $subtotal) {
        $query = "INSERT INTO " . $this->table . " (order_id, menu_item_id, quantity, subtotal) VALUES (:order_id, :menu_item_id, :quantity, :subtotal)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':menu_item_id', $menu_item_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':subtotal', $subtotal);
        return $stmt->execute();
    }

    /**
     * Retrieves all items associated with a specific order, including the menu item name.
     *
     * @param int $order_id The ID of the order.
     * @return array Array of order items.
     */
    public function getByOrderId($order_id) {
        $query = "SELECT oi.*, m.name as item_name FROM " . $this->table . " oi JOIN menu_items m ON oi.menu_item_id = m.id WHERE oi.order_id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
