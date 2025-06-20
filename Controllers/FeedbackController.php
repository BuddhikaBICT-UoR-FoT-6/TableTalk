<?php
namespace Controllers;

use Core\JWT;
use Models\Feedback;
use Models\Order;

class FeedbackController {
    /**
     * Submits a customer feedback for a paid order.
     * Validates that the order exists, belongs to the customer table, and is fully paid.
     *
     * @return void
     */
    public function create() {
        // Customers submit feedback
        $tokenPayload = JWT::requireRole(['customer']);

        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->order_id) || !isset($data->rating)) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID and rating are required']);
            return;
        }

        if ($data->rating < 1 || $data->rating > 5) {
            http_response_code(400);
            echo json_encode(['error' => 'Rating must be between 1 and 5']);
            return;
        }

        // Verify order belongs to customer and is paid
        $orderModel = new Order();
        $order = $orderModel->findById($data->order_id);

        if (!$order || $order['table_id'] !== $tokenPayload['sub']) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        if ($order['status'] !== 'paid') {
            http_response_code(400);
            echo json_encode(['error' => 'Feedback can only be submitted for paid orders']);
            return;
        }

        $comment = isset($data->comment) ? $data->comment : null;

        $feedbackModel = new Feedback();
        $success = $feedbackModel->create($data->order_id, $data->rating, $comment);

        if ($success) {
            http_response_code(201);
            echo json_encode(['message' => 'Feedback submitted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to submit feedback']);
        }
    }

    /**
     * Retrieves overall aggregate feedback statistics and lists all feedback comments.
     * Accessible by chefs and admins.
     *
     * @return void
     */
    public function index() {
        JWT::requireRole(['chef', 'admin']);

        $feedbackModel = new Feedback();
        $stats = $feedbackModel->getAggregateStats();
        $recent = $feedbackModel->getAll();

        echo json_encode([
            'stats' => $stats,
            'recent' => $recent
        ]);
    }
}
