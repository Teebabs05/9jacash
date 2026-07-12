-- =====================================================================
-- Scanner / exploit-probe firewall support.
--
-- Adds IP ban tracking and a security event log used by
-- includes/firewall.php to auto-block vulnerability scanners, known
-- exploit-probe URLs, and malicious query strings.
--
-- Safe to run against an existing production database - only adds new
-- tables, never touches existing data. Run once via:
--   mysql -u USER -p DB_NAME < database/migrations/2026_07_12_security_firewall.sql
-- =====================================================================

CREATE TABLE IF NOT EXISTS `blocked_ips` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip_address` VARCHAR(45) NOT NULL,
    `reason` VARCHAR(255) DEFAULT NULL,
    `strikes` INT UNSIGNED NOT NULL DEFAULT 1,
    `blocked_until` DATETIME DEFAULT NULL COMMENT 'NULL = permanent block',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_bi_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `security_events` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip_address` VARCHAR(45) NOT NULL,
    `event_type` VARCHAR(40) NOT NULL COMMENT 'scanner_ua, exploit_path, waf_pattern, banned_ip_retry',
    `request_uri` VARCHAR(2048) DEFAULT NULL,
    `user_agent` VARCHAR(512) DEFAULT NULL,
    `detail` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_se_ip` (`ip_address`),
    KEY `idx_se_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
