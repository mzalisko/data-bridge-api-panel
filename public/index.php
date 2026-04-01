<?php

declare(strict_types=1);

/**
 * DataBridgeApi — Application Entry Point
 *
 * All HTTP requests are routed through this file.
 */

define('ROOT', dirname(__DIR__));
define('SRC', ROOT . '/src');
define('CONFIG', ROOT . '/config');
define('LOGS', ROOT . '/logs');

require SRC . '/Core/Autoloader.php';

$autoloader = new \App\Core\Autoloader();
$autoloader->register();

require CONFIG . '/config.php';

$router = new \App\Core\Router();

require ROOT . '/routes.php';

$router->dispatch();
