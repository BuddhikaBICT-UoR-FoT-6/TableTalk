<?php
namespace Models;

use Core\Database;
use PDO;

class Message {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function create($table_id, $sender, $message) {
        $stmt = $this->conn->prepare("INSERT INTO messages (table_id, sender, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$table_id, $sender, $message])) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getUnreadForTable($table_id) {
        $stmt = $this->conn->prepare("SELECT * FROM messages WHERE table_id = ? AND sender = 'chef' AND is_read = FALSE ORDER BY created_at ASC");
        $stmt->execute([$table_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUnreadForChef() {
        $stmt = $this->conn->prepare("SELECT * FROM messages WHERE sender = 'table' AND is_read = FALSE ORDER BY created_at ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getChatHistory($table_id) {
        $stmt = $this->conn->prepare("SELECT * FROM messages WHERE table_id = ? ORDER BY created_at ASC");
        $stmt->execute([$table_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsRead($id) {
        $stmt = $this->conn->prepare("UPDATE messages SET is_read = TRUE WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function clearTableMessages($table_id) {
        $stmt = $this->conn->prepare("DELETE FROM messages WHERE table_id = ?");
        return $stmt->execute([$table_id]);
    }
}
