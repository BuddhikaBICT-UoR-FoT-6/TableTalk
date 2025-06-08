<?php
namespace Controllers;

use Models\Message;
use Core\JWT;

class MessageController {
    public function create() {
        $headers = getallheaders();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
        $decoded = JWT::decode($token);

        if (!$decoded || !in_array($decoded['role'], ['chef', 'customer', 'admin'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"));
        
        // If customer, they can only send to their own table
        $table_id = ($decoded['role'] === 'customer') ? $decoded['sub'] : $data->table_id;
        
        if (!$table_id || !isset($data->message)) {
            http_response_code(400);
            echo json_encode(['error' => 'Table ID and message required']);
            return;
        }

        $sender = ($decoded['role'] === 'customer') ? 'table' : 'chef';

        $messageModel = new Message();
        if ($messageModel->create($table_id, $sender, $data->message)) {
            http_response_code(201);
            echo json_encode(['message' => 'Message sent']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send message']);
        }
    }

    public function getUnread() {
        // Table polls for messages
        $headers = getallheaders();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
        $decoded = JWT::decode($token);

        if (!$decoded || $decoded['role'] !== 'customer') {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $messageModel = new Message();
        $messages = $messageModel->getUnreadForTable($decoded['sub']);

        http_response_code(200);
        echo json_encode(['data' => $messages]);
    }

    public function getUnreadChef() {
        // Chef polls for messages from any table
        $headers = getallheaders();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
        $decoded = JWT::decode($token);

        if (!$decoded || !in_array($decoded['role'], ['chef', 'admin'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $messageModel = new Message();
        $messages = $messageModel->getUnreadForChef();

        http_response_code(200);
        echo json_encode(['data' => $messages]);
    }

    public function getHistory($params) {
        $headers = getallheaders();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
        $decoded = JWT::decode($token);

        $table_id = $params['id'];

        // Customers can only see their own history
        if ($decoded['role'] === 'customer' && $decoded['sub'] !== $table_id) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $messageModel = new Message();
        $messages = $messageModel->getChatHistory($table_id);

        http_response_code(200);
        echo json_encode(['data' => $messages]);
    }

    public function markRead($params) {
        $id = $params['id'];
        // Either side can mark as read
        $headers = getallheaders();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
        $decoded = JWT::decode($token);

        if (!$decoded) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $messageModel = new Message();
        $messageModel->markAsRead($id);

        http_response_code(200);
        echo json_encode(['message' => 'Message marked as read']);
    }
}
