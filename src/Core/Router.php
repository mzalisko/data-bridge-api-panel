<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal HTTP router.
 *
 * Supports GET and POST route registration. Routes are matched against
 * REQUEST_URI with query string stripped. Placeholders are not supported
 * in this base version — extend as needed.
 *
 * Usage:
 *   $router->get('/api/v1/ping', [MyController::class, 'ping']);
 *   $router->post('/api/v1/auth/login', [AuthController::class, 'login']);
 *   $router->dispatch();
 */
class Router
{
    /** @var array<string, array<string, callable|array>> */
    private array $routes = [];

    /**
     * Register a GET route.
     *
     * @param string   $path    URI path (e.g. '/api/v1/ping')
     * @param callable $handler Any callable — closure or [class, method]
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    /**
     * Dispatch the current request to the matching route handler.
     * Supports parameterized routes with {name} placeholders.
     * Responds with 404 or 405 JSON if no match is found.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri    = '/' . trim((string) $uri, '/');

        // Exact match first
        if (isset($this->routes[$method][$uri])) {
            $handler = $this->routes[$method][$uri];
            if (is_array($handler) && is_string($handler[0])) {
                $handler = [new $handler[0](), $handler[1]];
            }
            call_user_func($handler);
            return;
        }

        // Pattern match — supports {name} placeholders (e.g. /sites/{id}/update)
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $pattern => $handler) {
                $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
                $regex = '@^' . $regex . '$@';
                if (preg_match($regex, $uri, $matches)) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    if (is_array($handler) && is_string($handler[0])) {
                        $handler = [new $handler[0](), $handler[1]];
                    }
                    call_user_func($handler, $params);
                    return;
                }
            }
        }

        // Check if URI matches any other method → 405
        foreach ($this->routes as $registeredMethod => $paths) {
            foreach ($paths as $pattern => $unused) {
                $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
                $regex = '@^' . $regex . '$@';
                if ($pattern === $uri || preg_match($regex, $uri)) {
                    http_response_code(405);
                    header('Content-Type: application/json');
                    echo json_encode([
                        'status'  => 'error',
                        'data'    => null,
                        'message' => 'Method not allowed.',
                    ]);
                    return;
                }
            }
        }

        // No match at all → 404
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'status'  => 'error',
            'data'    => null,
            'message' => 'Route not found.',
        ]);
    }
}
