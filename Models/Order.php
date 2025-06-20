<?php
namespace Models;

use Core\Database;
use PDO;

class Order {
    private $conn;
    private $table = 'orders';

    /**
     * Order constructor.
     * Initializes the database connection.
     */
    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Creates a new order.
     *
     * @param string $table_id The table ID.
     * @param float $total_amount Total cost of the order.
     * @param int $estimated_wait_minutes Expected time to prepare the order in minutes.
     * @param string|null $notes Additional user notes.
     * @return int|false The new order's ID, or false on failure.
     */
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

    /**
     * Gets the count of active orders (either pending or preparing).
     *
     * @return int Number of active orders.
     */
    public function getActiveOrderCount() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE status IN ('pending', 'preparing')";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? (int)$row['count'] : 0;
    }

    /**
     * Finds an order by its ID.
     *
     * @param int $id Order ID.
     * @return array|false The order details, or false if not found.
     */
    public function findById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Retrieves active kitchen orders (pending, preparing, or ready).
     *
     * @return array Array of active orders for the kitchen view.
     */
    public function getActiveKitchenOrders() {
        $query = "SELECT * FROM " . $this->table . " WHERE status IN ('pending', 'preparing', 'ready') ORDER BY created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retrieves all orders sorted by creation date descending.
     *
     * @return array Array of all orders.
     */
    public function getAllOrders() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retrieves active orders for a specific table.
     *
     * @param string $table_id Table ID.
     * @return array Array of active orders for the table.
     */
    public function getActiveOrdersForTable($table_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE table_id = :table_id AND status IN ('pending', 'preparing', 'ready', 'served') ORDER BY created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':table_id', $table_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Updates an order's status and estimated wait time.
     *
     * @param int $id The ID of the order.
     * @param string $status New status string.
     * @param int $estimated_wait_minutes New estimated wait time in minutes.
     * @return bool True on success, false on failure.
     */
    public function updateStatus($id, $status, $estimated_wait_minutes) {
        $query = "UPDATE " . $this->table . " SET status = :status, estimated_wait_minutes = :estimated_wait_minutes WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':estimated_wait_minutes', $estimated_wait_minutes);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
