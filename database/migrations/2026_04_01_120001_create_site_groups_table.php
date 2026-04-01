<?php

declare(strict_types=1);

use PDO;

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE site_groups (
                id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name        VARCHAR(150)    NOT NULL,
                description TEXT            NULL,
                created_by  BIGINT UNSIGNED NOT NULL,
                created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_site_groups_user FOREIGN KEY (created_by)
                    REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS site_groups");
    }
};
