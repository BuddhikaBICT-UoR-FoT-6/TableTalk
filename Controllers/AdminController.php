<?php
namespace Controllers;

use Core\JWT;
use Models\Order;
use Models\OrderItem;

class AdminController {
    /**
     * Retrieves all orders along with their detailed items.
     * Accessible only by users with the 'admin' role.
     *
     * @return void
     */
    public function getOrders() {
        JWT::requireRole(['admin']);

        $orderModel = new Order();
        $orders = $orderModel->getAllOrders();
        
        $orderItemModel = new OrderItem();
        foreach ($orders as &$order) {
            $order['items'] = $orderItemModel->getByOrderId($order['id']);
        }

        echo json_encode(['data' => $orders]);
    }
}
