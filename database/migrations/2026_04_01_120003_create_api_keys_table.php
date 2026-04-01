<?php

declare(strict_types=1);

use PDO;

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE api_keys (
                id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                site_id      BIGINT UNSIGNED NOT NULL,
                key_hash     VARCHAR(255)    NOT NULL,
                last_used_at TIMESTAMP       NULL,
                is_active    TINYINT(1)      NOT NULL DEFAULT 1,
                created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_api_keys_site_id (site_id),
                CONSTRAINT fk_api_keys_site FOREIGN KEY (site_id)
                    REFERENCES sites (id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS api_keys");
    }
};
