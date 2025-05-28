<?php
namespace Controllers;

use Core\JWT;
use Models\Order;
use Models\OrderItem;
use Models\MenuItem;

class OrderController {
    public function create() {
        $tokenPayload = JWT::requireRole(['customer']);
        
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->items) || !is_array($data->items) || count($data->items) === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Order items are required']);
            return;
        }

        $orderModel = new Order();
        $orderItemModel = new OrderItem();
        $menuModel = new MenuItem();

        $totalAmount = 0;
        $processedItems = [];

        foreach ($data->items as $item) {
            $menuItem = $menuModel->findById($item->menu_item_id);
            if (!$menuItem || !$menuItem['is_available']) {
                http_response_code(400);
                echo json_encode(['error' => 'Item not available or invalid ID: ' . $item->menu_item_id]);
                return;
            }
            $subtotal = $menuItem['price'] * $item->quantity;
            $totalAmount += $subtotal;
            $processedItems[] = [
                'menu_item_id' => $item->menu_item_id,
                'quantity' => $item->quantity,
                'subtotal' => $subtotal
            ];
        }

        // Create order
        $orderId = $orderModel->create($tokenPayload['sub'], $totalAmount);

        // Create order items
        foreach ($processedItems as $pItem) {
            $orderItemModel->create($orderId, $pItem['menu_item_id'], $pItem['quantity'], $pItem['subtotal']);
        }

        http_response_code(201);
        echo json_encode([
            'message' => 'Order created successfully',
            'order_id' => $orderId,
            'status' => 'pending',
            'estimated_wait_minutes' => 15,
            'total_amount' => $totalAmount
        ]);
    }

    public function show($params) {
        // Table can only see their own orders, or chef/admin can see any
        $tokenPayload = JWT::requireRole(['customer', 'chef', 'admin']);
        
        $orderId = $params['id'];
        $orderModel = new Order();
        $order = $orderModel->findById($orderId);

        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            return;
        }

        // Check if customer is accessing their own order
        if ($tokenPayload['role'] === 'customer' && $order['table_id'] !== $tokenPayload['sub']) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $orderItemModel = new OrderItem();
        $items = $orderItemModel->getByOrderId($orderId);
        $order['items'] = $items;

        echo json_encode(['data' => $order]);
    }
}
