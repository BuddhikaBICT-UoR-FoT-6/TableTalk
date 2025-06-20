<?php
namespace Core;

class Router {
    private $routes = [];

    /**
     * Registers a route in the application router.
     *
     * @param string $method The HTTP method (e.g., GET, POST).
     * @param string $uri The URI pattern for the route.
     * @param string $controllerAction The controller and method string in "Controller@method" format.
     * @return void
     */
    public function add($method, $uri, $controllerAction) {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'action' => $controllerAction
        ];
    }

    /**
     * Dispatches the incoming HTTP request to the matching controller action.
     * 
     * Parses the URI, resolves subdirectory pathways, performs regex matching
     * for route parameters, and invokes the target controller method if found.
     * Responds with 404 if no matching route is found.
     *
     * @param string $requestUri The requested URI.
     * @param string $requestMethod The HTTP method of the request.
     * @return void
     */
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
