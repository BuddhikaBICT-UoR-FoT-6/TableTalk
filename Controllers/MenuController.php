<?php
namespace Controllers;

use Models\MenuItem;

class MenuController {
    public function index() {
        $menuModel = new MenuItem();
        $items = $menuModel->getAllAvailable();
        
        echo json_encode(['data' => $items]);
    }

    public function create() {
        // Need to check if chef or admin
        $headers = getallheaders();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
        $decoded = \Core\JWT::decode($token);

        if (!$decoded || !in_array($decoded['role'], ['chef', 'admin'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        // FormData sends data in $_POST, not php://input JSON
        $name = $_POST['name'] ?? null;
        $price = $_POST['price'] ?? null;
        $category = $_POST['category'] ?? null;
        
        if (!$name || !$price || !$category) {
            http_response_code(400);
            echo json_encode(['error' => 'Name, price, and category are required']);
            return;
        }

        $rating = $_POST['rating'] ?? 5.0;
        $description = $_POST['description'] ?? '';
        
        $image_url = null;
        
        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../public/images/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileInfo = pathinfo($_FILES['image']['name']);
            $ext = strtolower($fileInfo['extension']);
            
            // Allow only basic image types
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $newFilename = uniqid('dish_') . '.' . $ext;
                $destination = $uploadDir . $newFilename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    $image_url = '/images/' . $newFilename;
                }
            }
        }

        $menuModel = new MenuItem();
        if ($menuModel->create($name, $category, $price, $description, $rating, $image_url)) {
            http_response_code(201);
            echo json_encode(['message' => 'Dish added successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add dish']);
        }
    }
}
