<?php

declare(strict_types=1);

namespace App\Core;

use PDOException;

/**
 * Application logger.
 *
 * Writes structured log lines to a daily rotating file under /logs/.
 * Optionally persists entries to the `logs` database table when a
 * Database instance is available.
 *
 * Log line format:
 *   [2026-04-01 14:05:32] [ERROR] [Auth] Failed login attempt {"ip":"127.0.0.1"}
 *
 * Levels: DEBUG, INFO, WARNING, ERROR, CRITICAL
 *
 * Usage:
 *   $logger = new Logger('Auth');
 *   $logger->info('User logged in', ['user_id' => 42]);
 *   $logger->error('Query failed', ['query' => '...']);
 */
class Logger
{
    private const LEVELS = ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'];

    private string $module;
    private string $logDir;
    private bool   $writeToDb;

    public function __construct(string $module, bool $writeToDb = false)
    {
        $this->module    = strtoupper($module);
        $this->logDir    = defined('LOGS') ? LOGS : dirname(__DIR__, 2) . '/logs';
        $this->writeToDb = $writeToDb;
    }

    // -------------------------------------------------------------------------
    // Convenience level methods
    // -------------------------------------------------------------------------

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    // -------------------------------------------------------------------------
    // Core log method
    // -------------------------------------------------------------------------

    /**
     * Write a log entry at the given level.
     *
     * @param string  $level   One of LEVELS
     * @param string  $message Human-readable message
     * @param mixed[] $context Optional key-value pairs serialised as JSON
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $level = strtoupper($level);
        if (!in_array($level, self::LEVELS, true)) {
            $level = 'INFO';
        }

        $datetime    = date('Y-m-d H:i:s');
        $contextJson = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $line        = "[{$datetime}] [{$level}] [{$this->module}] {$message}{$contextJson}" . PHP_EOL;

        $this->writeToFile($line);

        if ($this->writeToDb) {
            $this->writeToDatabase($level, $message, $context, $datetime);
        }
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function writeToFile(string $line): void
    {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }

        $filename = $this->logDir . '/app-' . date('Y-m-d') . '.log';

        // FILE_APPEND | LOCK_EX for safe concurrent writes
        file_put_contents($filename, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Persist the log entry to the `logs` DB table.
     * Fails silently so a DB error never breaks the application flow.
     */
    private function writeToDatabase(string $level, string $message, array $context, string $datetime): void
    {
        try {
            $pdo  = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare(
                'INSERT INTO logs (level, module, message, context, created_at)
                 VALUES (:level, :module, :message, :context, :created_at)'
            );
            $stmt->execute([
                ':level'      => $level,
                ':module'     => $this->module,
                ':message'    => $message,
                ':context'    => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ':created_at' => $datetime,
            ]);
        } catch (PDOException) {
            // Intentionally swallowed — log DB failures to file only
            $fallback = "[{$datetime}] [ERROR] [Logger] Failed to write log entry to database" . PHP_EOL;
            file_put_contents($this->logDir . '/app-' . date('Y-m-d') . '.log', $fallback, FILE_APPEND | LOCK_EX);
        }
    }
}
