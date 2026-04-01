<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Runs database migrations in order, tracking executed files in a `migrations` table.
 *
 * Each migration file must return an anonymous class with:
 *   public function up(PDO $pdo): void
 *   public function down(PDO $pdo): void
 */
class MigrationRunner
{
    private PDO $pdo;
    private string $migrationsPath;

    public function __construct(PDO $pdo, string $migrationsPath)
    {
        $this->pdo            = $pdo;
        $this->migrationsPath = rtrim($migrationsPath, '/\\');
        $this->ensureMigrationsTable();
    }

    /**
     * Run all pending migrations in filename order.
     */
    public function run(): void
    {
        $files   = $this->getPendingFiles();
        $batch   = $this->getNextBatch();

        if (empty($files)) {
            echo "Nothing to migrate.\n";
            return;
        }

        foreach ($files as $file) {
            $this->runMigration($file, $batch);
        }
    }

    /**
     * Roll back the last batch of migrations.
     */
    public function rollback(): void
    {
        $batch = $this->getLastBatch();

        if ($batch === 0) {
            echo "Nothing to roll back.\n";
            return;
        }

        $stmt = $this->pdo->prepare(
            'SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC'
        );
        $stmt->execute([$batch]);
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($migrations as $name) {
            $file = $this->migrationsPath . '/' . $name;
            if (!is_file($file)) {
                echo "  [SKIP]  $name — file not found\n";
                continue;
            }

            $migration = require $file;
            $migration->down($this->pdo);

            $this->pdo->prepare('DELETE FROM migrations WHERE migration = ?')
                      ->execute([$name]);

            echo "  [DOWN]  $name\n";
        }
    }

    // -------------------------------------------------------------------------

    private function ensureMigrationsTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration  VARCHAR(255) NOT NULL UNIQUE,
                batch      INT UNSIGNED NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    private function getPendingFiles(): array
    {
        $all = glob($this->migrationsPath . '/*.php') ?: [];
        sort($all);

        $stmt = $this->pdo->query('SELECT migration FROM migrations');
        $done = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_filter($all, fn($f) => !in_array(basename($f), $done, true));
    }

    private function getNextBatch(): int
    {
        $stmt = $this->pdo->query('SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations');
        return (int) $stmt->fetchColumn();
    }

    private function getLastBatch(): int
    {
        $stmt = $this->pdo->query('SELECT COALESCE(MAX(batch), 0) FROM migrations');
        return (int) $stmt->fetchColumn();
    }

    private function runMigration(string $file, int $batch): void
    {
        $name      = basename($file);
        $migration = require $file;

        $migration->up($this->pdo);

        $this->pdo->prepare('INSERT INTO migrations (migration, batch) VALUES (?, ?)')
                  ->execute([$name, $batch]);

        echo "  [UP]    $name\n";
    }
}
