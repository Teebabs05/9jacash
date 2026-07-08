-- =====================================================================
-- Adds: per-user/admin login notification email toggle, bulk email
-- (queued + cron-processed), and admin broadcast "push" notifications.
-- Safe to run against an existing production database.
--   mysql -u USER -p DB_NAME < database/migrations/2026_07_09_notification_prefs_bulk_email_broadcast.sql
-- =====================================================================

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `login_notifications_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Whether a "new login" email is sent to this user when they sign in' AFTER `payout_schedule`;

ALTER TABLE `admins`
    ADD COLUMN IF NOT EXISTS `login_notifications_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Whether a "new login" email is sent to this admin when they sign in' AFTER `status`;

CREATE TABLE IF NOT EXISTS `broadcast_campaigns` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id` INT UNSIGNED DEFAULT NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `recipient_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_broadcast_admin` (`admin_id`),
    CONSTRAINT `fk_broadcast_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bulk_emails` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id` INT UNSIGNED DEFAULT NULL,
    `subject` VARCHAR(200) NOT NULL,
    `body` TEXT NOT NULL,
    `audience` ENUM('all','selected') NOT NULL DEFAULT 'all',
    `total_recipients` INT UNSIGNED NOT NULL DEFAULT 0,
    `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('pending','processing','completed') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_bulk_emails_status` (`status`),
    CONSTRAINT `fk_bulk_emails_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bulk_email_recipients` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bulk_email_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `status` ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    `sent_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_ber_bulk_email` (`bulk_email_id`),
    KEY `idx_ber_status` (`status`),
    CONSTRAINT `fk_ber_bulk_email` FOREIGN KEY (`bulk_email_id`) REFERENCES `bulk_emails` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ber_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
