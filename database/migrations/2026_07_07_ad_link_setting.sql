-- =====================================================================
-- Adds the "Advert Link" setting used by the new admin Watch & Earn
-- settings page (admin/watch-settings.php). Safe to run against an
-- existing production database - only inserts the setting if missing.
--   mysql -u USER -p DB_NAME < database/migrations/2026_07_07_ad_link_setting.sql
-- =====================================================================

INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('ad_link', '')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
