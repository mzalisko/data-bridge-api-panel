<?php

declare(strict_types=1);

/**
 * Main application configuration.
 *
 * Loads environment variables from /.env (if present) via parse_ini_file,
 * then defines application-wide constants.
 *
 * Constants defined here:
 *   APP_ENV    — 'production' | 'development' | 'testing'
 *   APP_DEBUG  — bool
 *   APP_URL    — base URL (no trailing slash)
 *   APP_SECRET — secret key for signing / hashing
 *
 * Database credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS) are also loaded
 * here so that /config/database.php can read them as constants.
 */

// ---------------------------------------------------------------------------
// Load .env file if it exists
// ---------------------------------------------------------------------------

$envFile = defined('ROOT') ? ROOT . '/.env' : dirname(__DIR__) . '/.env';

if (is_file($envFile)) {
    $env = parse_ini_file($envFile, false, INI_SCANNER_TYPED);

    if ($env !== false) {
        foreach ($env as $key => $value) {
            // Only define if not already set (allows real environment variables to win)
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }
}

// ---------------------------------------------------------------------------
// Application constants — fall back to safe defaults
// ---------------------------------------------------------------------------

if (!defined('APP_ENV')) {
    define('APP_ENV', 'production');
}

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', false);
}

if (!defined('APP_URL')) {
    define('APP_URL', 'http://localhost');
}

if (!defined('APP_SECRET')) {
    // A missing secret in production is a configuration error
    define('APP_SECRET', 'change-me-in-env');
}

// ---------------------------------------------------------------------------
// Database constant fall-backs (used by /config/database.php)
// ---------------------------------------------------------------------------

if (!defined('DB_HOST')) {
    define('DB_HOST', '127.0.0.1');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', 'data_bridge');
}

if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

// ---------------------------------------------------------------------------
// Error display — never show errors in production
// ---------------------------------------------------------------------------

if (APP_DEBUG === true) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}
