<?php

declare(strict_types=1);

use PDO;

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE prices (
                id         BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
                site_id    BIGINT UNSIGNED  NOT NULL,
                label      VARCHAR(150)     NOT NULL,
                price      DECIMAL(10,2)    NOT NULL,
                currency   VARCHAR(10)      NOT NULL DEFAULT 'UAH',
                period     VARCHAR(50)      NULL,
                is_active  TINYINT(1)       NOT NULL DEFAULT 1,
                sort_order INT              NOT NULL DEFAULT 0,
                created_at TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_prices_site_id (site_id),
                CONSTRAINT fk_prices_site FOREIGN KEY (site_id)
                    REFERENCES sites (id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS prices");
    }
};
