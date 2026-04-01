<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * PDO database wrapper — singleton pattern.
 *
 * Connection parameters are sourced from /config/database.php.
 * Only one connection is created per request lifecycle.
 *
 * Usage:
 *   $db  = Database::getInstance();
 *   $pdo = $db->getConnection();
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $config = require defined('CONFIG') ? CONFIG . '/database.php' : dirname(__DIR__, 2) . '/config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['database'],
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            // Do not expose credentials or internals outside of debug mode
            $debug = defined('APP_DEBUG') && APP_DEBUG === true;
            $message = $debug ? $e->getMessage() : 'Database connection failed.';
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'error',
                'data'    => null,
                'message' => $message,
            ]);
            exit;
        }
    }

    /**
     * Prevent cloning of the singleton.
     */
    private function __clone(): void {}

    /**
     * Return the singleton instance.
     */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Return the underlying PDO connection.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}
