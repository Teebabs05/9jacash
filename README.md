# 9JACASH

A production-grade online earning platform (mining, tasks, referrals, ads,
spin wheel, daily check-in, wallet, deposits/withdrawals) built entirely in
**Core PHP 8.2+, MySQL/PDO, Bootstrap 5 and vanilla JavaScript** — no
frameworks.

> This repository is being built module by module. See **Build Status**
> below for what is implemented today.

## Tech Stack

- PHP 8.2+ (PDO, no framework)
- MySQL 8 / MariaDB 10.6+
- Bootstrap 5 (vendored locally, no CDN dependency)
- Vanilla JavaScript (AJAX where needed)
- PHPMailer (the only Composer dependency, per project spec)
- Self-hosted Poppins font + Bootstrap Icons (no Google Fonts / icon CDN)

## Requirements

- PHP >= 8.2 with `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `gd` (optional)
- MySQL/MariaDB
- Apache with `mod_rewrite` (shared hosting / cPanel compatible)

## Installation

1. Upload the entire project to your web root (or a subdirectory).
2. Create a MySQL database and user in cPanel (or your host's control panel).
3. Visit `https://yourdomain.com/install/` in your browser.
4. Follow the 4-step wizard:
   1. **Requirements Check** — verifies PHP version, extensions and folder permissions.
   2. **Database Configuration** — enter your DB host/name/user/password; the wizard tests the connection.
   3. **Site & Administrator Setup** — set your site name/URL and create your admin login. This step imports `database.sql` automatically.
   4. **Finish** — the wizard writes `config.php` and locks itself with `install/installed.lock`.
5. **Delete or restrict `/install`** after installation (the wizard blocks itself once `install/installed.lock` exists, but removing the folder is best practice).

A default administrator is also seeded directly inside `database.sql` in case
you import the schema manually instead of using the wizard:

```
Username: admin
Password: 1988125012
```

Change this password immediately after your first login. The install wizard
lets you overwrite these credentials during Step 3.

### Manual installation (without the wizard)

1. Import `database.sql` into your database.
2. Copy `.env.example` to `.env` and adjust mail/PayVessel values as needed.
3. Edit `config.php` at the project root with your real `DB_*` constants and `APP_URL`.
4. Create `install/installed.lock` (any content) so the app stops redirecting to the installer.

### Cron Jobs

Add this to your cPanel Cron Jobs (or crontab) once the platform is installed:

```
0 * * * * /usr/bin/php /home/USERNAME/public_html/cron/mining-payout.php >> /home/USERNAME/public_html/logs/cron.log 2>&1
```

Runs hourly and credits any mining position whose `next_payout_at` has
elapsed — it's idempotent, so running it more or less often than once a
day is safe.

## Folder Structure

```
9jacash/
├── admin/              Admin panel (login, dashboard, management modules)
├── ads/                Watch-to-earn ad reward module (upcoming)
├── ajax/                AJAX endpoints shared across modules
├── api/                 External/webhook API endpoints (PayVessel, etc.)
├── assets/              css/, js/, images/, fonts/ (all self-hosted, no CDN)
├── config/              Bootstrap, DB connection, constants, .env loader
├── cron/                Scheduled jobs (mining payouts, etc.)
├── database/            Migration/reference SQL snapshots
├── database.sql         Full schema + seed data (single source of truth)
├── includes/            Shared PHP services: Auth, Mailer, Wallet, security
├── install/             4-step installation wizard
├── logs/                Application + PHP error logs
├── mining/              Mining plans module (upcoming)
├── payments/            Deposit gateway integration (upcoming)
├── tasks/                Task center module (upcoming)
├── uploads/              KYC docs, receipts, task screenshots, avatars
├── user/                 Registration, login, verification, password reset, dashboard
├── vendor/               Composer dependencies (PHPMailer only)
├── wallet/               Wallet/withdrawal pages (upcoming)
├── .env.example          Environment variable template
├── .htaccess             Apache security + rewrite rules
├── composer.json
├── config.php            Generated app config (DB creds + APP_URL + APP_KEY)
└── index.php
```

## Security Implemented

- PDO prepared statements everywhere (no raw string interpolation into SQL)
- `password_hash()` / `password_verify()` (bcrypt, cost 12)
- CSRF tokens on every state-changing form (`includes/security.php`)
- Session hardening: httponly + samesite cookies, periodic ID regeneration
- Brute-force protection: 5 failed attempts → 15 minute lockout (`login_attempts` table)
- Selector/validator "remember me" tokens (never store the raw cookie value)
- Output escaping via `e()` helper (`htmlspecialchars` wrapper)
- Upload validation by real MIME sniffing (`finfo`), not file extension
- `uploads/` and internal folders (`config`, `includes`, `database`, `logs`) blocked from direct web access via `.htaccess`
- Activity logging (`activity_logs`) for auth events and admin actions
- Generic (non-enumerable) responses on forgot-password requests

## Build Status

### ✅ Module 1 — Foundation, Database, Installer, Authentication
- Full folder structure and `.htaccess` hardening
- `.env` support + generated `config.php` + `config/constants.php`
- Complete `database.sql` covering every module in the spec (users, wallets,
  referrals, mining, tasks, ads, spin wheel, check-ins, deposits,
  withdrawals, KYC, notifications, activity logs, site settings)
- 4-step installation wizard (`/install`)
- Registration, login, logout, remember-me, email verification,
  resend-verification, forgot/reset password
- Admin login + minimal admin dashboard shell
- Core wallet ledger primitives (`includes/wallet.php`) used by every future earning module
- Premium fintech design system (`assets/css/theme.css`) with light/dark mode, self-hosted Poppins + Bootstrap Icons, glassmorphism cards, toasts, skeleton loaders

### ✅ Module 2 — Wallet System + Public Landing Page
- Shared authenticated app shell (`includes/partials/app-*.php` +
  `assets/css/app.css`) — collapsible sidebar, topbar with live unread
  notification count and user menu — reused by the dashboard and every
  future user-facing module
- `wallet/index.php` — balance tiles (main/bonus/referral/mining) + recent
  transactions + referral link
- `wallet/history.php` — full ledger with wallet/source/type/date filters
  and pagination
- `user/notifications.php` — real notification feed with mark-all-read
- `user/profile.php` — edit name/phone/avatar, change password
- Full public landing page (`index.php`) — animated hero, features grid,
  how-it-works, live mining plans pulled from the database, referral
  program breakdown (reads live percentages from `site_settings`),
  animated stats counters (real user/payout counts), testimonials, FAQ
  accordion, supported payment methods, newsletter signup and contact
  form (both AJAX, CSRF-protected, backed by `newsletter_subscribers` and
  `contact_messages` tables)
- Seeded starter mining plans (Starter/Bronze/Silver/Gold Miner) so the
  landing page and future Mining module have real data to work with

### ✅ Module 3 — Mining System
- `includes/mining.php` — core primitives: `mining_purchase_plan()`,
  `mining_toggle_status()` (pause/resume), `mining_process_payouts()`
- `mining/index.php` — available plans + "My Mining Positions" table with
  a live progress bar, ticking countdown to next payout, and pause/resume
  controls
- `mining/invest.php` — confirmation screen (price, daily return,
  duration, total return, wallet balance check) before debiting the main
  wallet and opening the position
- `cron/mining-payout.php` — CLI cron job that credits every due position
  via `wallet_credit()`, advances `next_payout_at`, and marks a position
  `completed` once its cycle ends; safe to run as often as your host
  allows since it only pays out positions that are actually due

### ✅ Module 4 — Task Center (+ first Admin panel modules)
- Shared admin app shell (`includes/partials/admin-*.php`) — sidebar with
  a live pending-submissions badge, reused by every future admin page
- `admin/tasks.php` — full CRUD for tasks (Facebook/Telegram/Instagram/
  WhatsApp/TikTok/website/custom platforms, reward amount, screenshot
  requirement toggle, active/inactive)
- `admin/task-submissions.php` — review queue filterable by status;
  approving credits the reward to the user's **bonus wallet** via
  `wallet_credit()` and notifies them, rejecting records an optional
  reason shown to the user
- `tasks/index.php` — active tasks grouped with the user's own submission
  status (none/pending/approved/rejected)
- `tasks/submit.php` — proof submission with optional/required screenshot
  upload (real MIME-sniffed validation, stored outside script execution)
- `admin/index.php` rebuilt on the shared shell with live stat tiles
  (users, pending deposits/withdrawals, active mining positions)

### ✅ Module 5 — Watch-to-Earn Ads + Spin Wheel + Daily Check-in
- `includes/ads.php` — rewarded-ad flow enforced server-side: a session
  token records when the simulated ad started, and the reward can only
  be claimed once the configured watch duration has actually elapsed,
  with a daily cap and per-watch cooldown (`ads/index.php`,
  `ajax/ads-start.php`, `ajax/ads-claim.php`)
- `includes/spin.php` — weighted-probability draw against active
  `spin_settings` rows; `spin/index.php` renders a real conic-gradient
  wheel and animates it to the server-decided segment
  (`ajax/spin-play.php`); `admin/spin-settings.php` gives admins full
  CRUD over segments, odds, colors and the daily spin limit
- `includes/checkin.php` — streak tracking that resets on a missed day
  and wraps at day 30, with 7-day and 30-day milestone bonus multipliers
  (`checkin/index.php`)
- All three credit the **bonus wallet** via the same `wallet_credit()`
  primitive used since Module 3

Verified live: an early ad-reward claim is correctly rejected until the
watch duration elapses, daily/cooldown limits enforced, a spin wheel play
credits the wallet and blocks a second spin same-day (and immediately
allows more once an admin raises the daily limit), and check-in streak
math verified across a normal day, a day-7 milestone (correct 3x bonus),
and a missed day correctly resetting the streak to 1.

**Real bug caught and fixed during this verification:** MySQL's session
defaults to the server's system timezone (UTC in this environment) while
the app runs in `Africa/Lagos`. Anything that stored a timestamp via SQL
`NOW()`/`CURDATE()` and later compared it against PHP's `time()` (ad
cooldown countdowns, "watched today" checks) would silently drift by the
difference between the two zones — e.g. an ad's cooldown appeared to
expire immediately instead of after 30 seconds. Fixed by syncing MySQL's
session `time_zone` to PHP's active default timezone at connection time
(`Database::syncTimezone()` in `config/database.php`), with PHP's
timezone now set *before* the database connects instead of after.

### Planned next
Deposits (PayVessel + manual) → Withdrawals → Remaining admin management
modules (users, deposits, withdrawals, mining plans, settings) →
Branding asset pack (PNG exports, social banner, app icon) → Full
documentation set (Admin Guide, Cron Guide, PayVessel Integration Guide,
API Docs).

## License

Proprietary — all rights reserved.
