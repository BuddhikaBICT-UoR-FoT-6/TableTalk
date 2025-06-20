<?php
namespace Controllers;

use Core\JWT;
use Models\User;

class AuthController {
    /**
     * Authenticates a user (chef/admin) using email and password.
     * Generates and returns a JWT if credentials are valid.
     *
     * @return void
     */
    public function login() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->email) || !isset($data->password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password are required']);
            return;
        }

        $userModel = new User();
        $user = $userModel->findByEmail($data->email);

        if ($user && password_verify($data->password, $user['password_hash'])) {
            $payload = [
                'sub' => $user['id'],
                'role' => $user['role'],
                'name' => $user['name'],
                'exp' => time() + (60 * 60 * 24) // 1 day expiration
            ];
            $token = JWT::encode($payload);
            
            echo json_encode([
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    }

    /**
     * Creates a customer session token associated with a specific table ID.
     *
     * @return void
     */
    public function tableLogin() {
        // Generates a simple token for table sessions
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->table_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Table ID is required']);
            return;
        }

        $payload = [
            'sub' => $data->table_id,
            'role' => 'customer',
            'exp' => time() + (60 * 60 * 12) // 12 hours
        ];
        
        $token = JWT::encode($payload);
        
        echo json_encode([
            'message' => 'Table session created',
            'token' => $token,
            'table_id' => $data->table_id
        ]);
    }
}
