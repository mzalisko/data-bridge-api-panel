<?php

declare(strict_types=1);

use PDO;

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE logs (
                id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                level      ENUM('info','warning','error','debug') NOT NULL DEFAULT 'info',
                module     VARCHAR(50)     NOT NULL,
                action     VARCHAR(100)    NOT NULL,
                user_id    BIGINT UNSIGNED NULL,
                site_id    BIGINT UNSIGNED NULL,
                ip         VARCHAR(45)     NOT NULL,
                context    JSON            NULL,
                created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_logs_module_created (module, created_at),
                CONSTRAINT fk_logs_user FOREIGN KEY (user_id)
                    REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT fk_logs_site FOREIGN KEY (site_id)
                    REFERENCES sites (id) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS logs");
    }
};
