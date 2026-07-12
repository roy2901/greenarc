-- GreenArc Solutions - database schema
-- Import once via cPanel > phpMyAdmin (select your database, Import this file).

CREATE TABLE IF NOT EXISTS `leads` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(200) NOT NULL,
  `company`    VARCHAR(150) NULL,
  `message`    TEXT         NOT NULL,
  `status`     ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new',
  `source`     VARCHAR(50)  NOT NULL DEFAULT 'website',
  `ip_hash`    CHAR(64)     NULL,
  `user_agent` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status`  (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
