<?php
namespace Models;

use Core\Database;
use PDO;

class MenuItem {
    private $conn;
    private $table = 'menu_items';

    /**
     * MenuItem constructor.
     * Initializes the database connection.
     */
    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Retrieves all menu items that are marked as available.
     *
     * @return array Array of available menu items.
     */
    public function getAllAvailable() {
        $query = "SELECT * FROM " . $this->table . " WHERE is_available = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Finds a single menu item by its ID.
     *
     * @param int $id The ID of the menu item.
     * @return array|false The menu item record, or false if not found.
     */
    public function findById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Creates a new menu item.
     *
     * @param string $name Name of the menu item.
     * @param string $category Category of the item (e.g. Starter, Main).
     * @param float $price Price of the item.
     * @param string $description Description text.
     * @param float $rating Initial rating score.
     * @param string|null $image_url Optional URL of the item image.
     * @return bool True on success, false on failure.
     */
    public function create($name, $category, $price, $description, $rating, $image_url = null) {
        $query = "INSERT INTO " . $this->table . " (name, category, price, description, rating, image_url) VALUES (:name, :category, :price, :description, :rating, :image_url)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':image_url', $image_url);
        return $stmt->execute();
    }

    /**
     * Retrieves all menu items ordered by category and name.
     *
     * @return array Array of all menu items.
     */
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY category, name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Updates an existing menu item.
     *
     * @param int $id The ID of the item.
     * @param string $name Name of the item.
     * @param string $category Category of the item.
     * @param float $price Price.
     * @param string $description Description.
     * @param float $rating Rating.
     * @param string|null $image_url Optional image URL.
     * @param bool $is_available Availability status.
     * @return bool True on success, false on failure.
     */
    public function update($id, $name, $category, $price, $description, $rating, $image_url = null, $is_available = true) {
        $query = "UPDATE " . $this->table . " 
                  SET name = :name, category = :category, price = :price, 
                      description = :description, rating = :rating, is_available = :is_available";
        
        if ($image_url !== null) {
            $query .= ", image_url = :image_url";
        }
        $query .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':is_available', $is_available, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id);
        
        if ($image_url !== null) {
            $stmt->bindParam(':image_url', $image_url);
        }
        
        return $stmt->execute();
    }

    /**
     * Performs a soft delete on a menu item by setting its availability to 0.
     *
     * @param int $id The ID of the item.
     * @return bool True on success, false on failure.
     */
    public function delete($id) {
        // Soft delete
        $query = "UPDATE " . $this->table . " SET is_available = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
