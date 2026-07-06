-- =====================================================================
-- Mining payout release schedule (admin-configurable: daily / weekly /
-- every 2 weeks / at end of mining cycle), globally and per-user.
--
-- Safe to run against an existing production database - only adds new
-- columns/settings, never touches existing data. Run once via:
--   mysql -u USER -p DB_NAME < database/migrations/2026_07_06_mining_payout_schedule.sql
-- =====================================================================

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `payout_schedule` ENUM('default','daily','weekly','biweekly','cycle_end') NOT NULL DEFAULT 'default' AFTER `kyc_status`;

ALTER TABLE `user_mining`
    ADD COLUMN IF NOT EXISTS `next_release_at` DATETIME DEFAULT NULL AFTER `next_payout_at`,
    ADD COLUMN IF NOT EXISTS `released_earned` DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `total_earned`;

INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('mining_payout_schedule', 'daily')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
