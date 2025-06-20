<?php
namespace Controllers;

use Core\JWT;
use Models\Order;
use Models\OrderItem;
use Models\MenuItem;

class OrderController {
    /**
     * Creates a new customer order.
     * Validates that items exist and are available, calculates subtotal/total,
     * computes dynamic estimated wait time, and writes order details.
     *
     * @return void
     */
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

        // Dynamic wait time: 10 mins base + 5 mins per active order
        $activeOrders = $orderModel->getActiveOrderCount();
        $estimatedWait = 10 + ($activeOrders * 5);
        $notes = isset($data->notes) ? $data->notes : null;

        // Create order
        $orderId = $orderModel->create($tokenPayload['sub'], $totalAmount, $estimatedWait, $notes);

        // Create order items
        foreach ($processedItems as $pItem) {
            $orderItemModel->create($orderId, $pItem['menu_item_id'], $pItem['quantity'], $pItem['subtotal']);
        }

        http_response_code(201);
        echo json_encode([
            'message' => 'Order created successfully',
            'order_id' => $orderId,
            'status' => 'pending',
            'estimated_wait_minutes' => $estimatedWait,
            'total_amount' => $totalAmount
        ]);
    }

    /**
     * Retrieves details of a specific order, including order items.
     * Customers are restricted to viewing only their own table's orders.
     *
     * @param array $params Contains route parameter keys, including 'id' of the order.
     * @return void
     */
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

    /**
     * Retrieves active orders for the currently authenticated customer table.
     *
     * @return void
     */
    public function getActiveForTable() {
        $tokenPayload = JWT::requireRole(['customer']);
        $orderModel = new Order();
        $orders = $orderModel->getActiveOrdersForTable($tokenPayload['sub']);
        
        if ($orders && count($orders) > 0) {
            $orderItemModel = new OrderItem();
            foreach ($orders as &$order) {
                $order['items'] = $orderItemModel->getByOrderId($order['id']);
            }
            echo json_encode(['data' => $orders]);
        } else {
            echo json_encode(['data' => []]);
        }
    }
}
