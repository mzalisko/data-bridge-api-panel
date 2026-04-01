<?php

declare(strict_types=1);

use PDO;

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE phones (
                id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                site_id    BIGINT UNSIGNED NOT NULL,
                number     VARCHAR(30)     NOT NULL,
                label      VARCHAR(100)    NULL,
                is_primary TINYINT(1)      NOT NULL DEFAULT 0,
                is_active  TINYINT(1)      NOT NULL DEFAULT 1,
                sort_order INT             NOT NULL DEFAULT 0,
                created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_phones_site_id (site_id),
                CONSTRAINT fk_phones_site FOREIGN KEY (site_id)
                    REFERENCES sites (id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS phones");
    }
};
