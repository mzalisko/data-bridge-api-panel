<?php

declare(strict_types=1);

/**
 * Database connection configuration.
 *
 * Returns an array consumed by \App\Core\Database.
 * All values are read from constants defined in /config/config.php,
 * which sources them from the .env file.
 *
 * Never hard-code credentials here — use .env instead.
 */

return [
    'host'     => defined('DB_HOST') ? DB_HOST : '127.0.0.1',
    'database' => defined('DB_NAME') ? DB_NAME : 'data_bridge',
    'username' => defined('DB_USER') ? DB_USER : 'root',
    'password' => defined('DB_PASS') ? DB_PASS : '',
];
