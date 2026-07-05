-- =====================================================================
-- 9JACASH — Sample / default data
-- Run AFTER schema.sql (the installer does this automatically).
-- Default admin: username "admin" / password "1988125012"
-- Password change is FORCED on first login (force_password_change = 1).
-- =====================================================================

INSERT INTO users
    (full_name, username, email, phone, password_hash, country, state, role, status, referral_code, email_verified_at, kyc_status, force_password_change)
VALUES
    ('Site Administrator', 'admin', 'admin@9jacash.com', '2348000000000',
     '$2y$12$pFoo0DtrLG3TDWZdh/rbyundHpXojdM9ZFHvcZzN.1V4FwJ9TACpS',
     'Nigeria', 'Lagos', 'admin', 'active', 'ADMIN9JC', NOW(), 'not_required', 1);

INSERT INTO wallets (user_id) VALUES (LAST_INSERT_ID());

-- ---------------------------------------------------------------------
-- Core settings
-- ---------------------------------------------------------------------
INSERT INTO settings (`key`, `value`) VALUES
    ('site_name', '9JACASH'),
    ('site_tagline', 'Earn. Grow. Cash Out.'),
    ('currency_symbol', '₦'),
    ('currency_code', 'NGN'),
    ('primary_color', '#0D47A1'),
    ('secondary_color', '#1565C0'),
    ('accent_color', '#FFC107'),
    ('logo_path', ''),
    ('favicon_path', ''),
    ('maintenance_mode', '0'),
    ('kyc_required', '0'),
    ('registration_bonus', '100'),
    ('min_deposit', '500'),
    ('max_deposit', '1000000'),
    ('min_withdrawal', '1000'),
    ('max_withdrawal', '500000'),
    ('daily_withdrawal_limit', '1'),
    ('withdrawal_charge_percent', '2'),
    ('referral_signup_bonus', '50'),
    ('referral_deposit_percent', '5'),
    ('referral_mining_percent', '3'),
    ('referral_task_percent', '2'),
    ('referral_level_2_percent', '1'),
    ('referral_level_3_percent', '0.5'),
    ('checkin_base_reward', '10'),
    ('spin_min_reward', '5'),
    ('spin_max_reward', '200'),
    ('recaptcha_enabled', '0'),
    ('email_verification_required', '1'),
    ('smtp_configured', '0'),
    ('social_facebook', ''),
    ('social_twitter', ''),
    ('social_instagram', ''),
    ('social_telegram', ''),
    ('contact_email', 'support@9jacash.com'),
    ('contact_phone', '+2348000000000'),
    ('announcement_text', 'Welcome to 9JACASH — start earning today!'),
    ('announcement_active', '1');

-- ---------------------------------------------------------------------
-- Sample manual payment method
-- ---------------------------------------------------------------------
INSERT INTO payment_methods (bank_name, account_name, account_number, instructions, is_active) VALUES
    ('Access Bank', '9JACASH LIMITED', '0123456789', 'Use your username as the payment narration/description, then upload your receipt below.', 1);

-- ---------------------------------------------------------------------
-- Sample mining plans
-- ---------------------------------------------------------------------
INSERT INTO mining_plans (name, price, daily_profit, duration_days, total_roi_percent, max_users, status) VALUES
    ('Starter Miner', 2000.00, 150.00, 20, 150.00, NULL, 'active'),
    ('Bronze Miner', 5000.00, 400.00, 25, 200.00, NULL, 'active'),
    ('Silver Miner', 15000.00, 1350.00, 30, 270.00, NULL, 'active'),
    ('Gold Miner', 50000.00, 5000.00, 30, 300.00, 500, 'active'),
    ('Diamond Miner', 150000.00, 18000.00, 30, 360.00, 100, 'active');

-- ---------------------------------------------------------------------
-- Sample tasks
-- ---------------------------------------------------------------------
INSERT INTO tasks (title, description, category, link, reward_amount, requires_proof, repeatable, status) VALUES
    ('Follow us on Facebook', 'Follow the official 9JACASH Facebook page and submit a screenshot.', 'facebook_like', 'https://facebook.com', 50.00, 1, 'once', 'active'),
    ('Join our Telegram channel', 'Join the 9JACASH Telegram community for updates.', 'telegram_join', 'https://t.me', 50.00, 1, 'once', 'active'),
    ('Follow us on Instagram', 'Follow @9jacash on Instagram.', 'instagram_follow', 'https://instagram.com', 50.00, 1, 'once', 'active'),
    ('Watch our intro video', 'Watch the full onboarding video on YouTube.', 'watch_video', 'https://youtube.com', 30.00, 0, 'once', 'active'),
    ('Daily login bonus', 'Log in every day to earn a small reward.', 'daily_login', NULL, 10.00, 0, 'daily', 'active'),
    ('Refer a friend', 'Invite a friend using your referral link.', 'referral_task', NULL, 100.00, 0, 'daily', 'active');

-- ---------------------------------------------------------------------
-- Sample announcement
-- ---------------------------------------------------------------------
INSERT INTO announcements (title, message, is_active) VALUES
    ('Welcome to 9JACASH', 'Complete your KYC and start mining today to unlock higher daily rewards!', 1);

-- ---------------------------------------------------------------------
-- Sample coupon
-- ---------------------------------------------------------------------
INSERT INTO coupons (code, amount, max_uses, status) VALUES
    ('WELCOME50', 50.00, 1000, 'active');
