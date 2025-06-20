<?php
namespace Models;

use Core\Database;
use PDO;

class User {
    private $conn;
    private $table = 'users';

    /**
     * User constructor.
     * Initializes the database connection.
     */
    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Finds a user by their email address.
     *
     * @param string $email The email address of the user.
     * @return array|false The user record as an associative array, or false if not found.
     */
    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch();
    }
}
