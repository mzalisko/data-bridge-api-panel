<?php

declare(strict_types=1);

/**
 * Migration CLI runner.
 *
 * Usage (from project root):
 *   php database/migrate.php          — run pending migrations
 *   php database/migrate.php rollback — roll back last batch
 */

define('ROOT',   dirname(__DIR__));
define('SRC',    ROOT . '/src');
define('CONFIG', ROOT . '/config');
define('LOGS',   ROOT . '/logs');

require SRC . '/Core/Autoloader.php';

$autoloader = new \App\Core\Autoloader();
$autoloader->register();

require CONFIG . '/config.php';

$pdo    = \App\Core\Database::getInstance()->getConnection();
$runner = new \App\Core\MigrationRunner($pdo, ROOT . '/database/migrations');

$command = $argv[1] ?? 'migrate';

echo "\n=== DataBridgeApi Migration Runner ===\n\n";

match ($command) {
    'rollback' => $runner->rollback(),
    default    => $runner->run(),
};

echo "\nDone.\n";
