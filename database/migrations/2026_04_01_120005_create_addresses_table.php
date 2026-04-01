<?php

declare(strict_types=1);

use PDO;

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE addresses (
                id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                site_id     BIGINT UNSIGNED NOT NULL,
                street      VARCHAR(255)    NULL,
                city        VARCHAR(100)    NULL,
                region      VARCHAR(100)    NULL,
                country     VARCHAR(100)    NULL,
                postal_code VARCHAR(20)     NULL,
                label       VARCHAR(100)    NULL,
                is_primary  TINYINT(1)      NOT NULL DEFAULT 0,
                created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_addresses_site_id (site_id),
                CONSTRAINT fk_addresses_site FOREIGN KEY (site_id)
                    REFERENCES sites (id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS addresses");
    }
};
