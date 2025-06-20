<?php
namespace Controllers;

use Core\JWT;
use Models\Order;
use Models\Payment;

class PaymentController {
    /**
     * Processes payment for a customer order.
     * Validates that the order exists, status is served, and the payment amount matches.
     * On success, clears chat history if no other active orders exist for the table.
     *
     * @return void
     */
    public function process() {
        // Customer or admin can trigger payment
        $tokenPayload = JWT::requireRole(['customer', 'admin']);

        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->order_id) || !isset($data->amount) || !isset($data->method)) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID, amount, and method are required']);
            return;
        }

        $orderModel = new Order();
        $order = $orderModel->findById($data->order_id);

        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            return;
        }

        // Must be served to be paid, or maybe we allow it when ready/served. Let's say it must be "served".
        if ($order['status'] !== 'served' && $order['status'] !== 'paid') {
            http_response_code(400);
            echo json_encode(['error' => 'Order must be served before payment can be processed']);
            return;
        }

        if ($order['status'] === 'paid') {
            echo json_encode(['message' => 'Order is already paid']);
            return;
        }

        // Validate amount matches
        if ((float)$order['total_amount'] !== (float)$data->amount) {
            http_response_code(400);
            echo json_encode(['error' => 'Payment amount does not match order total']);
            return;
        }

        $paymentModel = new Payment();
        $success = $paymentModel->process($data->order_id, $data->amount, $data->method);

        if ($success) {
            // Check if there are other active orders for the table
            $activeOrders = $orderModel->getActiveOrdersForTable($order['table_id']);
            if (empty($activeOrders)) {
                // Clear chat messages for this table
                $messageModel = new \Models\Message();
                $messageModel->clearTableMessages($order['table_id']);
            }

            echo json_encode(['message' => 'Payment processed successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Payment processing failed']);
        }
    }
}
