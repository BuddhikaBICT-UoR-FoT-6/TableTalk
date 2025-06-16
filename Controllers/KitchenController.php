<?php
namespace Controllers;

use Core\JWT;
use Models\Order;
use Models\OrderItem;

class KitchenController {
    public function index() {
        JWT::requireRole(['chef', 'admin']);

        $orderModel = new Order();
        $orders = $orderModel->getActiveKitchenOrders();
        
        $orderItemModel = new OrderItem();
        foreach ($orders as &$order) {
            $order['items'] = $orderItemModel->getByOrderId($order['id']);
        }

        echo json_encode(['data' => $orders]);
    }

    public function updateStatus($params) {
        JWT::requireRole(['chef', 'admin']);

        $orderId = $params['id'];
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->status)) {
            http_response_code(400);
            echo json_encode(['error' => 'Status is required']);
            return;
        }

        $validStatuses = ['pending', 'preparing', 'ready', 'served', 'paid'];
        if (!in_array($data->status, $validStatuses)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status']);
            return;
        }

        $orderModel = new Order();
        $existingOrder = $orderModel->findById($orderId);
        
        $estimatedWait = isset($data->estimated_wait_minutes) ? $data->estimated_wait_minutes : $existingOrder['estimated_wait_minutes'];
        
        $updated = $orderModel->updateStatus($orderId, $data->status, $estimatedWait);

        if ($updated) {
            echo json_encode(['message' => 'Order updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update order']);
        }
    }
}
