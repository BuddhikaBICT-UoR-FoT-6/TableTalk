<?php
// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple autoloader for namespace
spl_autoload_register(function ($class) {
    $classFile = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    $file = __DIR__ . '/' . $classFile;
    if (file_exists($file)) {
        require_once $file;
    }
});

use Core\Router;

$router = new Router();

// Auth routes
$router->add('POST', '/api/auth/login', 'AuthController@login');
$router->add('POST', '/api/auth/table', 'AuthController@tableLogin');

// Menu routes
$router->add('GET', '/api/menu', 'MenuController@index');
$router->add('GET', '/api/menu/all', 'MenuController@getAll');
$router->add('POST', '/api/menu', 'MenuController@create'); // Chef adds dish
$router->add('POST', '/api/menu/update/:id', 'MenuController@update'); // Chef updates dish
$router->add('DELETE', '/api/menu/:id', 'MenuController@delete'); // Chef deletes dish

// Orders routes (Customer)
$router->add('POST', '/api/orders', 'OrderController@create');
$router->add('GET', '/api/orders/active', 'OrderController@getActiveForTable');
$router->add('GET', '/api/orders/:id', 'OrderController@show');

// Kitchen routes (Chef)
$router->add('GET', '/api/kitchen/orders', 'KitchenController@index');
$router->add('PUT', '/api/kitchen/orders/:id', 'KitchenController@updateStatus');

// Admin routes
$router->add('GET', '/api/admin/orders', 'AdminController@getOrders');

// Payment routes
$router->add('POST', '/api/payments', 'PaymentController@process');

// Receipt route
$router->add('POST', '/api/receipt', 'ReceiptController@send');

// Feedback routes
$router->add('POST', '/api/feedback', 'FeedbackController@create');
$router->add('GET', '/api/feedback', 'FeedbackController@index');

// Message routes
$router->add('POST', '/api/messages', 'MessageController@create');
$router->add('GET', '/api/messages/unread', 'MessageController@getUnread'); // For table
$router->add('GET', '/api/messages/chef/unread', 'MessageController@getUnreadChef'); // For chef
$router->add('GET', '/api/messages/:id/history', 'MessageController@getHistory'); // For chat UI
$router->add('PUT', '/api/messages/:id/read', 'MessageController@markRead');

// Dispatch the request
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Redirect root to public/
if ($requestUri === '/') {
    header("Location: /public/");
    exit();
}

// Handle static files from /public directory if accessed without /public prefix
if (strpos($requestUri, '/api') !== 0 && $requestUri !== '/') {
    // Check if the file exists in the public directory
    $publicUri = str_replace('/public', '', $requestUri); // in case they explicitly type /public
    $file = __DIR__ . '/public' . $publicUri;
    
    // If it's a directory, default to index.html
    if (is_dir($file)) {
        $file = rtrim($file, '/') . '/index.html';
    }

    if (file_exists($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimes = [
            'html' => 'text/html',
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'json' => 'application/json',
            'png'  => 'image/png'
        ];
        if (isset($mimes[$ext])) {
            header("Content-Type: " . $mimes[$ext]);
        }
        readfile($file);
        exit();
    }
}

// All API routes
header("Content-Type: application/json; charset=UTF-8");


if (strpos($requestUri, '/api') === 0 || strpos($requestUri, '/TableTalk/api') !== false) {
    $router->dispatch($requestUri, $requestMethod);
} else {
    // If not API and not matching a file, return 404 for API or redirect
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
}
