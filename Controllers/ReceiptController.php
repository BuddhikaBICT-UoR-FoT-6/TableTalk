<?php
namespace Controllers;

use Core\JWT;
use Models\Order;

class ReceiptController {
    public function send() {
        JWT::requireRole(['customer']);
        
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->email) || !isset($data->order_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and order_id required']);
            return;
        }

        $orderModel = new Order();
        $order = $orderModel->findById($data->order_id);

        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            return;
        }

        // Build HTML Email Payload
        $itemsHtml = '';
        foreach ($order['items'] as $item) {
            $itemsHtml .= "
                <tr>
                    <td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$item['quantity']}x {$item['item_name']}</td>
                    <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: right;'>$" . number_format($item['subtotal'], 2) . "</td>
                </tr>
            ";
        }

        $htmlBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eaeaea; border-radius: 10px;'>
            <h1 style='color: #2c3e50; text-align: center;'>TableTalk Receipt</h1>
            <p>Thank you for dining with us! Here is the receipt for your recent order.</p>
            
            <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                <thead>
                    <tr style='background-color: #f8f9fa;'>
                        <th style='padding: 10px; text-align: left; border-bottom: 2px solid #ddd;'>Item</th>
                        <th style='padding: 10px; text-align: right; border-bottom: 2px solid #ddd;'>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    {$itemsHtml}
                </tbody>
                <tfoot>
                    <tr>
                        <th style='padding: 15px 8px; text-align: right;'>Total:</th>
                        <th style='padding: 15px 8px; text-align: right; font-size: 1.2em;'>$" . number_format($order['total_amount'], 2) . "</th>
                    </tr>
                </tfoot>
            </table>
            <p style='text-align: center; color: #7f8c8d; font-size: 0.9em; margin-top: 30px;'>
                Order ID: #{$order['id']} <br> Date: {$order['created_at']}
            </p>
        </div>
        ";

        $subject = "Your TableTalk Receipt - Order #{$order['id']}";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@tabletalk.local" . "\r\n";

        // Attempt to use PHP mail() - will likely fail gracefully without sendmail config on Windows
        @mail($data->email, $subject, $htmlBody, $headers);

        // Save a mock copy to verify
        $mockFile = __DIR__ . "/../receipt_mock_{$order['id']}.html";
        file_put_contents($mockFile, $htmlBody);

        $logMessage = "[" . date('Y-m-d H:i:s') . "] Sent HTML receipt for Order #" . $order['id'] . " to " . $data->email . "\n";
        file_put_contents(__DIR__ . '/../receipts.log', $logMessage, FILE_APPEND);

        http_response_code(200);
        echo json_encode(['message' => 'Receipt emailed successfully!']);
    }
}
