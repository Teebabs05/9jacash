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

### 🔜 Next: Module 2 — Wallet Pages + Public Landing Page
Full wallet dashboard (ledger history, filters), and the marketing landing
page (hero, features, mining plans preview, referral program, testimonials,
FAQ, footer) to replace the current placeholder `index.php` redirect.

### Planned after that
Mining system → Task center → Watch-to-earn ads → Spin wheel → Daily
check-in → Deposits (PayVessel + manual) → Withdrawals → Notifications →
Admin management modules (users, deposits, withdrawals, mining, tasks,
settings) → Branding asset pack (PNG exports, social banner, app icon) →
Full documentation set (Admin Guide, Cron Guide, PayVessel Integration
Guide, API Docs).

## License

Proprietary — all rights reserved.
