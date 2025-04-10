<?php
// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

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

// Orders routes (Customer)
$router->add('POST', '/api/orders', 'OrderController@create');
$router->add('GET', '/api/orders/:id', 'OrderController@show');

// Kitchen routes (Chef)
$router->add('GET', '/api/kitchen/orders', 'KitchenController@index');
$router->add('PUT', '/api/kitchen/orders/:id', 'KitchenController@updateStatus');

// Payment routes
$router->add('POST', '/api/payments', 'PaymentController@process');

// Feedback routes
$router->add('POST', '/api/feedback', 'FeedbackController@create');
$router->add('GET', '/api/feedback', 'FeedbackController@index');

// Dispatch the request
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Handle static files if using built-in server
if (php_sapi_name() === 'cli-server' && strpos($requestUri, '/api') !== 0 && $requestUri !== '/') {
    $file = __DIR__ . '/public' . $requestUri;
    if (file_exists($file)) {
        return false; // serve the requested resource as-is
    }
}

// Redirect root to public/index.html
if ($requestUri === '/') {
    header("Location: /public/");
    exit();
}

if (strpos($requestUri, '/api') === 0 || strpos($requestUri, '/TableTalk/api') !== false) {
    $router->dispatch($requestUri, $requestMethod);
} else {
    // If not API and not matching a file, return 404 for API or redirect
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
}
