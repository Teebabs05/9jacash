-- =====================================================================
-- SURECASH MINING - Complete Production Database Schema
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION';

-- =====================================================================
-- ADMINISTRATORS
-- =====================================================================
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(150) NOT NULL DEFAULT 'Administrator',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('super_admin','admin','moderator','support') NOT NULL DEFAULT 'admin',
    `status` ENUM('active','disabled') NOT NULL DEFAULT 'active',
    `last_login_at` DATETIME DEFAULT NULL,
    `last_login_ip` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_admins_username` (`username`),
    UNIQUE KEY `uniq_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- USERS
-- =====================================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(150) NOT NULL,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `country` VARCHAR(80) DEFAULT 'Nigeria',
    `referral_code` VARCHAR(20) NOT NULL,
    `referred_by` INT UNSIGNED DEFAULT NULL,
    `status` ENUM('active','suspended','banned') NOT NULL DEFAULT 'active',
    `kyc_status` ENUM('unverified','pending','approved','rejected') NOT NULL DEFAULT 'unverified',
    `payout_schedule` ENUM('default','daily','weekly','biweekly','cycle_end') NOT NULL DEFAULT 'default' COMMENT 'Per-user override for when mining earnings release to the withdrawable wallet; default = use site_settings.mining_payout_schedule',
    `email_verified_at` DATETIME DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `last_login_ip` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_users_username` (`username`),
    UNIQUE KEY `uniq_users_email` (`email`),
    UNIQUE KEY `uniq_users_referral_code` (`referral_code`),
    KEY `idx_users_referred_by` (`referred_by`),
    KEY `idx_users_status` (`status`),
    CONSTRAINT `fk_users_referred_by` FOREIGN KEY (`referred_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- EMAIL VERIFICATION / PASSWORD RESET / REMEMBER ME / RATE LIMITING
-- =====================================================================
CREATE TABLE IF NOT EXISTS `email_verifications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ev_user` (`user_id`),
    CONSTRAINT `fk_ev_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `used` TINYINT(1) NOT NULL DEFAULT 0,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pr_user` (`user_id`),
    CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `remember_tokens` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `selector` VARCHAR(24) NOT NULL,
    `hashed_validator` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_rt_selector` (`selector`),
    KEY `idx_rt_user` (`user_id`),
    CONSTRAINT `fk_rt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `identifier` VARCHAR(191) NOT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `last_attempt_at` DATETIME DEFAULT NULL,
    `blocked_until` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_la_identifier` (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- FIREWALL / SCANNER PROTECTION
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

-- =====================================================================
-- WALLET SYSTEM
-- =====================================================================
CREATE TABLE IF NOT EXISTS `wallets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `main_balance` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `bonus_balance` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `referral_balance` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `mining_balance` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `pending_balance` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_wallets_user` (`user_id`),
    CONSTRAINT `fk_wallets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wallet_ledger` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `wallet_type` ENUM('main','bonus','referral','mining','pending') NOT NULL,
    `type` ENUM('credit','debit') NOT NULL,
    `amount` DECIMAL(18,2) NOT NULL,
    `balance_after` DECIMAL(18,2) NOT NULL,
    `reference` VARCHAR(100) DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `source` ENUM('deposit','withdrawal','mining','task','ad','spin','checkin','referral','admin_adjustment','transfer') NOT NULL,
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_wl_user` (`user_id`),
    KEY `idx_wl_source` (`source`),
    KEY `idx_wl_created` (`created_at`),
    CONSTRAINT `fk_wl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- REFERRAL SYSTEM
-- =====================================================================
CREATE TABLE IF NOT EXISTS `referrals` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'the referrer',
    `referred_id` INT UNSIGNED NOT NULL COMMENT 'the referred user',
    `level` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_referrals_pair_level` (`user_id`, `referred_id`, `level`),
    KEY `idx_referrals_referred` (`referred_id`),
    CONSTRAINT `fk_referrals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_referrals_referred` FOREIGN KEY (`referred_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `referral_earnings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'who earned the bonus',
    `from_user_id` INT UNSIGNED NOT NULL COMMENT 'whose activity triggered it',
    `level` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `amount` DECIMAL(18,2) NOT NULL,
    `source` VARCHAR(50) NOT NULL DEFAULT 'deposit',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_re_user` (`user_id`),
    KEY `idx_re_from` (`from_user_id`),
    CONSTRAINT `fk_re_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_re_from_user` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- MINING SYSTEM
-- =====================================================================
CREATE TABLE IF NOT EXISTS `mining_plans` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `price` DECIMAL(18,2) NOT NULL,
    `daily_return` DECIMAL(18,2) NOT NULL,
    `duration_days` SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    `available_cycles` VARCHAR(50) NOT NULL DEFAULT '7,14,21,30' COMMENT 'Comma-separated day-cycle choices offered to the user at purchase time',
    `description` TEXT DEFAULT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_mining` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `plan_id` INT UNSIGNED NOT NULL,
    `amount_invested` DECIMAL(18,2) NOT NULL,
    `total_earned` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `released_earned` DECIMAL(18,2) NOT NULL DEFAULT 0.00 COMMENT 'How much of total_earned has been moved from the pending wallet into the withdrawable mining wallet so far',
    `started_at` DATETIME NOT NULL,
    `next_payout_at` DATETIME DEFAULT NULL,
    `next_release_at` DATETIME DEFAULT NULL COMMENT 'Next time accrued pending earnings release to the withdrawable mining wallet (weekly/biweekly schedules only)',
    `ends_at` DATETIME NOT NULL,
    `status` ENUM('active','paused','completed') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_um_user` (`user_id`),
    KEY `idx_um_plan` (`plan_id`),
    KEY `idx_um_status` (`status`),
    CONSTRAINT `fk_um_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_um_plan` FOREIGN KEY (`plan_id`) REFERENCES `mining_plans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mining_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_mining_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(18,2) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ml_user` (`user_id`),
    KEY `idx_ml_mining` (`user_mining_id`),
    CONSTRAINT `fk_ml_user_mining` FOREIGN KEY (`user_mining_id`) REFERENCES `user_mining` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ml_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- DAILY CHECK-IN
-- =====================================================================
CREATE TABLE IF NOT EXISTS `daily_checkins` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `streak_day` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `reward_amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `checkin_date` DATE NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_checkin_user_date` (`user_id`, `checkin_date`),
    CONSTRAINT `fk_checkin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SPIN WHEEL
-- =====================================================================
CREATE TABLE IF NOT EXISTS `spin_settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `label` VARCHAR(50) NOT NULL,
    `amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `probability` DECIMAL(6,2) NOT NULL DEFAULT 0.00 COMMENT 'weight out of 100',
    `color` VARCHAR(20) NOT NULL DEFAULT '#0F5132',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `spin_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `spin_setting_id` INT UNSIGNED DEFAULT NULL,
    `amount_won` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sl_user` (`user_id`),
    CONSTRAINT `fk_sl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sl_setting` FOREIGN KEY (`spin_setting_id`) REFERENCES `spin_settings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TASK CENTER
-- =====================================================================
CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `platform` ENUM('facebook','telegram','instagram','whatsapp','tiktok','website','custom') NOT NULL DEFAULT 'custom',
    `task_url` VARCHAR(255) DEFAULT NULL,
    `reward_amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `requires_screenshot` TINYINT(1) NOT NULL DEFAULT 1,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `task_submissions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `task_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `proof_text` VARCHAR(255) DEFAULT NULL,
    `screenshot` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `admin_note` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_task_user` (`task_id`, `user_id`),
    KEY `idx_ts_user` (`user_id`),
    KEY `idx_ts_status` (`status`),
    CONSTRAINT `fk_ts_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- WATCH ADS TO EARN
-- =====================================================================
CREATE TABLE IF NOT EXISTS `ads_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `reward_amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `watched_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_al_user` (`user_id`),
    CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- DEPOSITS
-- =====================================================================
CREATE TABLE IF NOT EXISTS `deposits` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `method` ENUM('payvessel','bank','usdt') NOT NULL,
    `amount` DECIMAL(18,2) NOT NULL,
    `charge` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `reference` VARCHAR(100) NOT NULL,
    `proof` VARCHAR(255) DEFAULT NULL,
    `gateway_response` TEXT DEFAULT NULL,
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `admin_note` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_deposits_reference` (`reference`),
    KEY `idx_deposits_user` (`user_id`),
    KEY `idx_deposits_status` (`status`),
    CONSTRAINT `fk_deposits_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- WITHDRAWALS
-- =====================================================================
CREATE TABLE IF NOT EXISTS `bank_accounts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `type` ENUM('bank','usdt') NOT NULL DEFAULT 'bank',
    `bank_name` VARCHAR(100) DEFAULT NULL,
    `account_number` VARCHAR(30) DEFAULT NULL,
    `account_name` VARCHAR(150) DEFAULT NULL,
    `usdt_address` VARCHAR(150) DEFAULT NULL,
    `network` VARCHAR(30) DEFAULT NULL,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ba_user` (`user_id`),
    CONSTRAINT `fk_ba_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `withdrawals` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `bank_account_id` INT UNSIGNED DEFAULT NULL,
    `method` ENUM('bank','usdt') NOT NULL,
    `amount` DECIMAL(18,2) NOT NULL,
    `charge` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `net_amount` DECIMAL(18,2) NOT NULL,
    `account_details` TEXT DEFAULT NULL,
    `status` ENUM('pending','processing','approved','rejected') NOT NULL DEFAULT 'pending',
    `admin_note` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `processed_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_withdrawals_user` (`user_id`),
    KEY `idx_withdrawals_status` (`status`),
    CONSTRAINT `fk_withdrawals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_withdrawals_bank` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- KYC
-- =====================================================================
CREATE TABLE IF NOT EXISTS `kyc_documents` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `document_type` VARCHAR(50) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `admin_note` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kyc_user` (`user_id`),
    CONSTRAINT `fk_kyc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- NOTIFICATIONS
-- =====================================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = broadcast to all users',
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('deposit','withdrawal','mining','referral','task','system','broadcast','support') NOT NULL DEFAULT 'system',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notifications_user` (`user_id`),
    KEY `idx_notifications_user_read` (`user_id`, `is_read`),
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- ACTIVITY / AUDIT LOGS
-- =====================================================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `admin_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_logs_user` (`user_id`),
    KEY `idx_logs_admin` (`admin_id`),
    KEY `idx_logs_action` (`action`),
    CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SITE SETTINGS (key-value store used across the whole platform)
-- =====================================================================
CREATE TABLE IF NOT EXISTS `site_settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` LONGTEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- PUBLIC LANDING PAGE: NEWSLETTER + CONTACT FORM
-- =====================================================================
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(150) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_newsletter_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `subject` VARCHAR(150) DEFAULT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_contact_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- DASHBOARD-TO-DASHBOARD SUPPORT MESSAGING
-- One running conversation thread per user; all messages in it are
-- rows here scoped by user_id regardless of which side sent them.
-- =====================================================================
CREATE TABLE IF NOT EXISTS `support_messages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `sender` ENUM('user','admin') NOT NULL,
    `admin_id` INT UNSIGNED DEFAULT NULL,
    `message` TEXT NOT NULL,
    `is_read_by_user` TINYINT(1) NOT NULL DEFAULT 0,
    `is_read_by_admin` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sm_user` (`user_id`),
    KEY `idx_sm_created` (`created_at`),
    CONSTRAINT `fk_sm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sm_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- BIOMETRIC LOGIN (WebAuthn/FIDO2 platform authenticators)
-- =====================================================================
CREATE TABLE IF NOT EXISTS `webauthn_credentials` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type` ENUM('user','admin') NOT NULL,
    `owner_id` INT UNSIGNED NOT NULL,
    `credential_id` VARCHAR(255) NOT NULL COMMENT 'base64url-encoded authenticator credential ID',
    `public_key` TEXT NOT NULL COMMENT 'PEM-encoded EC public key reconstructed from the COSE key',
    `sign_count` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `device_label` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_used_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_webauthn_credential_id` (`credential_id`),
    KEY `idx_webauthn_owner` (`owner_type`, `owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA
-- =====================================================================

-- Default administrator: username "admin", password "1988125012"
-- Hash generated with PHP password_hash('1988125012', PASSWORD_BCRYPT, ['cost' => 12])
INSERT INTO `admins` (`username`, `email`, `password`, `full_name`, `role`, `status`, `created_at`, `updated_at`)
VALUES (
    'admin',
    'admin@surecashmining.com',
    '$2y$12$qMnLsJXAlXds3rTkRIarQOc7hvzKxuY02YU3HxNSSwGG/pCRKjLfa',
    'Super Administrator',
    'super_admin',
    'active',
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE `username` = `username`;

-- Default site settings
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'SURECASH MINING'),
('site_tagline', 'Mine. Earn. Grow Wealth.'),
('currency', 'NGN'),
('currency_symbol', '₦'),
('timezone', 'Africa/Lagos'),
('usd_exchange_rate', '1500'),
('maintenance_mode', '0'),
('maintenance_message', 'We are currently performing scheduled maintenance. Please check back shortly.'),
('registration_bonus', '0'),
('referral_max_levels', '3'),
('referral_level_1_percent', '5'),
('referral_level_2_percent', '2'),
('referral_level_3_percent', '1'),
('min_deposit', '500'),
('max_deposit', '1000000'),
('payvessel_enabled', '0'),
('deposit_bank_name', ''),
('deposit_bank_account_number', ''),
('deposit_bank_account_name', ''),
('deposit_usdt_address', ''),
('deposit_usdt_network', 'TRC20'),
('min_withdrawal', '1000'),
('max_withdrawal', '500000'),
('withdrawal_charge_percent', '2'),
('daily_withdrawal_limit', '1'),
('ad_reward_amount', '10'),
('ad_daily_limit', '10'),
('ad_cooldown_seconds', '30'),
('ad_watch_duration_seconds', '15'),
('spin_daily_limit', '1'),
('spin_extra_price', '50'),
('mining_payout_schedule', 'daily'),
('checkin_base_reward', '10'),
('mail_from_name', 'SURECASH MINING'),
('mail_from_address', 'no-reply@surecashmining.com'),
('contact_email', 'support@surecashmining.com'),
('contact_phone', '+2348000000000'),
('whatsapp_number', ''),
('facebook_url', ''),
('twitter_url', ''),
('instagram_url', ''),
('telegram_url', ''),
('whatsapp_url', ''),
('playstore_url', ''),
('appstore_url', ''),
('google_analytics_id', ''),
('facebook_pixel_id', '')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

-- Default spin wheel segments
INSERT INTO `spin_settings` (`label`, `amount`, `probability`, `color`, `is_active`) VALUES
('₦50', 50.00, 30.00, '#0F5132', 1),
('₦100', 100.00, 25.00, '#0B2545', 1),
('₦200', 200.00, 15.00, '#F2C94C', 1),
('₦500', 500.00, 8.00, '#0F5132', 1),
('Try Again', 0.00, 15.00, '#8a94a6', 1),
('₦1000', 1000.00, 5.00, '#0B2545', 1),
('₦50', 50.00, 2.00, '#F2C94C', 1)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);

-- Starter mining plans (shown on the landing page and in the Mining module)
INSERT INTO `mining_plans` (`name`, `price`, `daily_return`, `duration_days`, `description`, `status`) VALUES
('Starter Miner', 2000.00, 150.00, 30, 'A low-risk entry plan perfect for first-time miners.', 'active'),
('Bronze Miner', 5000.00, 400.00, 30, 'Balanced daily returns with a 30-day mining cycle.', 'active'),
('Silver Miner', 15000.00, 1350.00, 30, 'Our most popular plan for consistent daily earners.', 'active'),
('Gold Miner', 50000.00, 4750.00, 30, 'Premium returns for serious investors.', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
