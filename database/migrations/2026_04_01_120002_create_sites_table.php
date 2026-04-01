<?php

declare(strict_types=1);

use PDO;

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE sites (
                id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                group_id   BIGINT UNSIGNED NOT NULL,
                name       VARCHAR(150)    NOT NULL,
                url        VARCHAR(255)    NOT NULL,
                is_active  TINYINT(1)      NOT NULL DEFAULT 1,
                created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_sites_group_id (group_id),
                CONSTRAINT fk_sites_group FOREIGN KEY (group_id)
                    REFERENCES site_groups (id) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS sites");
    }
};
