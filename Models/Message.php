<?php
namespace Models;

use Core\Database;
use PDO;

class Message {
    private $conn;

    /**
     * Message constructor.
     * Initializes the database connection.
     */
    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Creates a new chat message.
     *
     * @param string $table_id The identifier of the table.
     * @param string $sender The sender role ('table' or 'chef').
     * @param string $message The message text content.
     * @return int|false The last inserted ID on success, or false on failure.
     */
    public function create($table_id, $sender, $message) {
        $stmt = $this->conn->prepare("INSERT INTO messages (table_id, sender, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$table_id, $sender, $message])) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Retrieves all unread messages from the chef for a specific table.
     *
     * @param string $table_id The table identifier.
     * @return array Array of unread messages.
     */
    public function getUnreadForTable($table_id) {
        $stmt = $this->conn->prepare("SELECT * FROM messages WHERE table_id = ? AND sender = 'chef' AND is_read = FALSE ORDER BY created_at ASC");
        $stmt->execute([$table_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all unread messages from tables for the chef.
     *
     * @return array Array of unread messages.
     */
    public function getUnreadForChef() {
        $stmt = $this->conn->prepare("SELECT * FROM messages WHERE sender = 'table' AND is_read = FALSE ORDER BY created_at ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves full chat history for a specific table.
     *
     * @param string $table_id The table identifier.
     * @return array Array of message history records.
     */
    public function getChatHistory($table_id) {
        $stmt = $this->conn->prepare("SELECT * FROM messages WHERE table_id = ? ORDER BY created_at ASC");
        $stmt->execute([$table_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marks a specific message as read.
     *
     * @param int $id The message ID.
     * @return bool True on success, false on failure.
     */
    public function markAsRead($id) {
        $stmt = $this->conn->prepare("UPDATE messages SET is_read = TRUE WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Deletes all messages associated with a specific table.
     *
     * @param string $table_id The table identifier.
     * @return bool True on success, false on failure.
     */
    public function clearTableMessages($table_id) {
        $stmt = $this->conn->prepare("DELETE FROM messages WHERE table_id = ?");
        return $stmt->execute([$table_id]);
    }
}
