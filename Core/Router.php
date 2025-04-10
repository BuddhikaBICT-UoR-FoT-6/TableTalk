<?php
namespace Core;

class Router {
    private $routes = [];

    public function add($method, $uri, $controllerAction) {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'action' => $controllerAction
        ];
    }

    public function dispatch($requestUri, $requestMethod) {
        // Remove query string from URI
        $requestUri = parse_url($requestUri, PHP_URL_PATH);
        
        // Strip base path if running in a subdirectory (like XAMPP htdocs/TableTalk)
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptName !== '/' && strpos($requestUri, $scriptName) === 0) {
            $requestUri = substr($requestUri, strlen($scriptName));
        }
        
        foreach ($this->routes as $route) {
            // Convert route uri to regex
            $pattern = preg_replace('/\:([a-zA-Z0-9_]+)/', '(?P<$1>[a-zA-Z0-9_]+)', $route['uri']);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $requestUri, $matches) && $route['method'] === $requestMethod) {
                // Extract params
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                $action = explode('@', $route['action']);
                $controllerName = 'Controllers\\' . $action[0];
                $methodName = $action[1];

                if (class_exists($controllerName)) {
                    $controller = new $controllerName();
                    if (method_exists($controller, $methodName)) {
                        call_user_func_array([$controller, $methodName], [$params]);
                        return;
                    }
                }
            }
        }

        // Return 404 if no route matched
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
}
