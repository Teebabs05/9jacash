-- =====================================================================
-- Biometric login (WebAuthn/FIDO2 platform authenticators - Windows
-- Hello, Touch ID, Android biometric unlock) for both users and admins.
--
-- Safe to run against an existing production database - only adds a
-- new table, never touches existing data. Run once via:
--   mysql -u USER -p DB_NAME < database/migrations/2026_07_06_webauthn_credentials.sql
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
