<?php

declare(strict_types=1);

/**
 * Application route definitions.
 *
 * Register all GET/POST routes here against the $router instance
 * provided by public/index.php.
 *
 * Convention:
 *   $router->get('/api/v1/<resource>', [ControllerClass::class, 'method']);
 *   $router->post('/api/v1/<resource>', [ControllerClass::class, 'method']);
 *
 * Routes will be added as controllers are implemented in subsequent tasks.
 */

// -------------------------------------------------------------------------
// Health-check — confirms the application boots correctly
// -------------------------------------------------------------------------

$router->get('/api/v1/ping', static function (): void {
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'ok',
        'data'    => ['pong' => true],
        'message' => 'DataBridgeApi is running.',
    ]);
});
