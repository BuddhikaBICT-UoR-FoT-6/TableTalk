<?php
/**
 * Sri Lankan food database seeding script.
 * Alters the orders table to include a notes field, and inserts
 * initial authentic Sri Lankan menu item records (Kottu, Hoppers, etc.).
 */
require_once 'Core/Database.php';

use Core\Database;

try {
    $db = new Database();
    $conn = $db->connect();
    
    // 1. Add notes column to orders table
    try {
        $conn->exec("ALTER TABLE orders ADD COLUMN notes TEXT NULL AFTER total_amount");
        echo "Added 'notes' column to orders table.\n";
    } catch (PDOException $e) {
        echo "Note: " . $e->getMessage() . "\n";
    }

    // 2. Insert Sri Lankan Dishes
    $stmt = $conn->prepare("INSERT INTO menu_items (name, category, description, price, rating, image_url) VALUES (?, ?, ?, ?, ?, ?)");
    
    $dishes = [
        ['Kottu Roti', 'Foods', 'A popular Sri Lankan street food made with chopped flatbread, vegetables, egg, and spicy meat curry.', 12.50, 4.9, 'images/kottu.png'],
        ['Egg Hoppers', 'Foods', 'Crispy bowl-shaped fermented rice pancakes with a soft fried egg in the center, served with lunu miris.', 8.00, 4.7, 'images/hoppers.png'],
        ['Watalappan', 'Desserts', 'Rich and creamy coconut custard pudding sweetened with kithul jaggery and spiced with cardamom.', 6.50, 4.8, 'images/watalappan.png'],
        ['Ceylon Tea', 'Drinks', 'A perfect, robust cup of authentic Sri Lankan Ceylon black tea.', 3.50, 4.9, 'images/ceylon_tea.png']
    ];

    foreach ($dishes as $dish) {
        $stmt->execute($dish);
    }
    echo "Successfully seeded Sri Lankan dishes into the menu.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
