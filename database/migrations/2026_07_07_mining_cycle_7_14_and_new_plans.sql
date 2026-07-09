-- =====================================================================
-- Restrict mining investment cycles to 7 and 14 days only (removes the
-- 21-day and 30-day options everywhere), and add four higher-tier plans:
-- ₦100,000 / ₦150,000 / ₦200,000 / ₦250,000.
--
-- Safe to run against an existing production database - it only updates
-- available_cycles/duration_days on existing plans and inserts the new
-- plans if they don't already exist (matched by price). Positions users
-- have already started keep their original ends_at; this only changes
-- what cycle length is offered on new purchases going forward. Run once
-- via:
--   mysql -u USER -p DB_NAME < database/migrations/2026_07_07_mining_cycle_7_14_and_new_plans.sql
-- =====================================================================

ALTER TABLE `mining_plans`
    MODIFY COLUMN `available_cycles` VARCHAR(50) NOT NULL DEFAULT '7,14' COMMENT 'Comma-separated day-cycle choices offered to the user at purchase time';

-- Restrict every existing plan to 7/14-day cycles and bring its reference
-- duration (used for admin-assigned/gifted positions) in line.
UPDATE `mining_plans` SET `available_cycles` = '7,14' WHERE `available_cycles` <> '7,14';
UPDATE `mining_plans` SET `duration_days` = 14 WHERE `duration_days` NOT IN (7, 14);

INSERT INTO `mining_plans` (`name`, `price`, `daily_return`, `duration_days`, `available_cycles`, `description`, `status`)
SELECT * FROM (
    SELECT 'Platinum Miner' AS name, 100000.00 AS price, 10000.00 AS daily_return, 14 AS duration_days, '7,14' AS available_cycles, 'High-yield plan for experienced miners.' AS description, 'active' AS status
    UNION ALL SELECT 'Diamond Miner', 150000.00, 15750.00, 14, '7,14', 'Elevated daily returns for larger portfolios.', 'active'
    UNION ALL SELECT 'Titanium Miner', 200000.00, 22000.00, 14, '7,14', 'Premium-tier returns for major investors.', 'active'
    UNION ALL SELECT 'Elite Miner', 250000.00, 28750.00, 14, '7,14', 'Our top-tier plan with the highest daily returns.', 'active'
) AS new_plans
WHERE NOT EXISTS (SELECT 1 FROM `mining_plans` mp WHERE mp.price = new_plans.price);
