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
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    /**
     * Register a GET route.
     *
     * @param string   $path    URI path (e.g. '/api/v1/ping')
     * @param callable $handler Any callable — closure or [class, method]
     */
    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    /**
     * Dispatch the current request to the matching route handler.
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

            // Auto-instantiate [ClassName, 'method'] arrays
            if (is_array($handler) && is_string($handler[0])) {
                $handler = [new $handler[0](), $handler[1]];
            }

            call_user_func($handler);
            return;
        }

        // Check if the URI exists under a different method → 405
        foreach ($this->routes as $registeredMethod => $paths) {
            if (isset($paths[$uri])) {
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
