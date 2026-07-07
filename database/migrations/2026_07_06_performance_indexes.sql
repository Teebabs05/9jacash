-- =====================================================================
-- Adds missing indexes on columns that are filtered/counted on every
-- single page load (admin sidebar pending-task badge, user topbar
-- unread-notification badge) but had no supporting index, meaning
-- MySQL had to full-scan these tables on every request as they grow.
--
-- Safe to run against an existing production database - only adds
-- indexes, never touches data. Run once via:
--   mysql -u USER -p DB_NAME < database/migrations/2026_07_06_performance_indexes.sql
-- =====================================================================

ALTER TABLE `task_submissions`
    ADD INDEX IF NOT EXISTS `idx_ts_status` (`status`);

ALTER TABLE `notifications`
    ADD INDEX IF NOT EXISTS `idx_notifications_user_read` (`user_id`, `is_read`);
