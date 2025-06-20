<?php
/**
 * Feedback database seeding script.
 * Populates the database with realistic sample customer feedback,
 * corresponding mock orders, payment records, and order items.
 */
require_once __DIR__ . '/Core/Database.php';

$db = new \Core\Database();
$conn = $db->connect();

// Dummy feedbacks to seed
$feedbacks = [
    ['rating' => 5, 'comment' => 'The kottu was amazing and the service was super fast!', 'table_id' => '1', 'menu_item_ids' => [1, 2]],
    ['rating' => 4, 'comment' => 'Great food, but the wait time was slightly longer than expected.', 'table_id' => '3', 'menu_item_ids' => [3, 4]],
    ['rating' => 5, 'comment' => 'Absolutely loved the hopper meal. Authentic taste.', 'table_id' => '5', 'menu_item_ids' => [5]],
    ['rating' => 2, 'comment' => 'The falooda was too sweet for my liking.', 'table_id' => '2', 'menu_item_ids' => [6]],
    ['rating' => 5, 'comment' => 'Excellent service and great ambience. Will come back!', 'table_id' => '1', 'menu_item_ids' => [7, 8]],
    ['rating' => 4, 'comment' => 'Very good portion sizes.', 'table_id' => '4', 'menu_item_ids' => [2, 3, 5]]
];

$conn->beginTransaction();

try {
    foreach ($feedbacks as $fb) {
        // Create an order
        $stmt = $conn->prepare("INSERT INTO orders (table_id, status, estimated_wait_minutes, total_amount, created_at) VALUES (:table_id, 'paid', 0, 50.00, DATE_SUB(NOW(), INTERVAL FLOOR(RAND()*10) DAY))");
        $stmt->execute(['table_id' => $fb['table_id']]);
        $order_id = $conn->lastInsertId();

        // Create order items
        foreach ($fb['menu_item_ids'] as $menu_id) {
            $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, subtotal) VALUES (:order_id, :menu_id, 1, 10.00)");
            $item_stmt->execute(['order_id' => $order_id, 'menu_id' => $menu_id]);
        }

        // Create payment
        $pay_stmt = $conn->prepare("INSERT INTO payments (order_id, amount, method, status, paid_at) VALUES (:order_id, 50.00, 'card', 'completed', NOW())");
        $pay_stmt->execute(['order_id' => $order_id]);

        // Create feedback
        $fb_stmt = $conn->prepare("INSERT INTO feedback (order_id, rating, comment, created_at) VALUES (:order_id, :rating, :comment, DATE_SUB(NOW(), INTERVAL FLOOR(RAND()*10) DAY))");
        $fb_stmt->execute([
            'order_id' => $order_id,
            'rating' => $fb['rating'],
            'comment' => $fb['comment']
        ]);
    }

    $conn->commit();
    echo "Successfully seeded " . count($feedbacks) . " feedbacks.\n";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Failed to seed feedback: " . $e->getMessage() . "\n";
}
