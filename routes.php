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

// -------------------------------------------------------------------------
// Auth routes
// -------------------------------------------------------------------------

$router->get('/login', [\App\Auth\LoginController::class, 'showForm']);
$router->post('/login', [\App\Auth\LoginController::class, 'handleLogin']);
$router->get('/logout', [\App\Auth\LoginController::class, 'logout']);

// -------------------------------------------------------------------------
// Admin routes
// -------------------------------------------------------------------------

$router->get('/dashboard', [\App\Admin\DashboardController::class, 'index']);

// -------------------------------------------------------------------------
// Site Groups routes
// -------------------------------------------------------------------------

$router->get('/site-groups', [\App\Admin\SiteGroupsController::class, 'index']);

// Group CRUD
$router->post('/site-groups/create',      [\App\Admin\SiteGroupsController::class, 'createGroup']);
$router->post('/site-groups/{id}/update', [\App\Admin\SiteGroupsController::class, 'updateGroup']);
$router->post('/site-groups/{id}/delete', [\App\Admin\SiteGroupsController::class, 'deleteGroup']);

// Site CRUD
$router->post('/sites/create',            [\App\Admin\SiteGroupsController::class, 'createSite']);
$router->post('/sites/{id}/update',       [\App\Admin\SiteGroupsController::class, 'updateSite']);
$router->post('/sites/{id}/delete',       [\App\Admin\SiteGroupsController::class, 'deleteSite']);

// Drag & drop AJAX
$router->post('/api/v1/sites/{id}/move',  [\App\Admin\SiteGroupsController::class, 'moveSite']);

// -------------------------------------------------------------------------
// Root redirect
// -------------------------------------------------------------------------

$router->get('/', static function (): void {
    if (\App\Auth\AuthGuard::isLoggedIn()) {
        header('Location: /dashboard');
    } else {
        header('Location: /login');
    }
    exit;
});
