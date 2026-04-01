<?php

declare(strict_types=1);

use PDO;

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE social_networks (
                id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                site_id    BIGINT UNSIGNED NOT NULL,
                phone_id   BIGINT UNSIGNED NULL,
                platform   VARCHAR(50)     NOT NULL,
                url        VARCHAR(255)    NULL,
                username   VARCHAR(100)    NULL,
                label      VARCHAR(100)    NULL,
                is_active  TINYINT(1)      NOT NULL DEFAULT 1,
                sort_order INT             NOT NULL DEFAULT 0,
                created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_social_networks_site_id (site_id),
                INDEX idx_social_networks_phone_id (phone_id),
                CONSTRAINT fk_social_networks_site FOREIGN KEY (site_id)
                    REFERENCES sites (id) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT fk_social_networks_phone FOREIGN KEY (phone_id)
                    REFERENCES phones (id) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS social_networks");
    }
};
