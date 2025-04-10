<?php
namespace Core;

use PDO;
use PDOException;

class Database {
    private $host = '127.0.0.1';
    private $db_name = 'tabletalk';
    private $username = 'root';
    private $password = ''; // Default XAMPP password
    private $conn;

    public function connect() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            // For production, log error instead of outputting. 
            // For local development portfolio, outputting is fine.
            echo "Connection Error: " . $e->getMessage();
        }

        return $this->conn;
    }
}
