-- =====================================================================
-- Adds the 'moderator' staff role alongside the existing super_admin/
-- admin/support roles, for the new Staff Management screen.
--
-- Safe to run against an existing production database - only widens
-- the enum, never touches existing data. Run once via:
--   mysql -u USER -p DB_NAME < database/migrations/2026_07_06_moderator_role.sql
-- =====================================================================

ALTER TABLE `admins`
    MODIFY COLUMN `role` ENUM('super_admin','admin','moderator','support') NOT NULL DEFAULT 'admin';
