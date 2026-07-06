# SURECASH MINING

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
day is safe. See `docs/CRON_GUIDE.md` for verification steps and outage
behavior.

## Documentation

- `docs/ADMIN_GUIDE.md` — running the admin panel day to day
- `docs/CRON_GUIDE.md` — the mining payout scheduled job
- `docs/PAYVESSEL_INTEGRATION.md` — setting up automatic bank-transfer deposits
- `docs/API_DOCS.md` — internal AJAX endpoint + webhook reference

## Folder Structure

```
surecash-mining/
├── admin/              Admin panel (login, dashboard, management modules)
├── ads/                Watch-to-earn ad reward module
├── ajax/                AJAX endpoints shared across modules
├── api/                 External/webhook API endpoints (PayVessel, etc.)
├── assets/              css/, js/, images/, fonts/ (all self-hosted, no CDN)
├── config/              Bootstrap, DB connection, constants, .env loader
├── cron/                Scheduled jobs (mining payouts, etc.)
├── database/            Migration/reference SQL snapshots
├── database.sql         Full schema + seed data (single source of truth)
├── docs/                 Admin Guide, Cron Guide, PayVessel Integration Guide, endpoint reference
├── includes/            Shared PHP services: Auth, Mailer, Wallet, security
├── install/             4-step installation wizard
├── logs/                Application + PHP error logs
├── mining/              Mining plans module
├── payments/            Deposit gateway integration
├── tasks/                Task center module
├── uploads/              KYC docs, receipts, task screenshots, avatars
├── user/                 Registration, login, verification, password reset, dashboard
├── vendor/               Composer dependencies (PHPMailer only)
├── wallet/               Wallet/withdrawal pages
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
- Content-Security-Policy restricting script/style/img/font loading to the
  app's own origin (every asset is self-hosted, so this is a real
  restriction, not a no-op)
- IP-based rate limiting on registration, contact form and newsletter
  signup (in addition to login/password-reset), using the real TCP
  connection IP rather than a client-spoofable `X-Forwarded-For` header
  unless the site owner explicitly opts in via `TRUST_PROXY_HEADERS`
  (for deployments genuinely behind Cloudflare/a reverse proxy)
- Uploaded logos are restricted to raster formats (no SVG, which can
  embed `<script>`); the `uploads/` directory sends `nosniff` and forces
  download (`Content-Disposition: attachment`) for PDFs

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

### ✅ Module 6 — Deposits (PayVessel + Manual Bank/USDT)
- `includes/payvessel.php` — real PayVessel API client built against
  their documented "Create Virtual Account" and "Payment Notification"
  services (cross-checked against an open-source reference
  implementation, not guessed): generates a one-time (`DYNAMIC`) reserved
  account number per deposit, and verifies inbound webhooks via
  HMAC-SHA512 over the raw request body against the
  `Payvessel-Http-Signature` header. PayVessel's documented source IPs
  are checked as a logged, non-blocking signal (the signature is the
  real authenticity guarantee — IP alone breaks behind proxies/CDNs)
- `includes/deposits.php` — shared approve/reject/webhook-handler
  primitives; approval credits the **main wallet** via `wallet_credit()`
  and is idempotent (replaying a webhook or re-approving a reviewed
  deposit is a safe no-op)
- `payments/deposit.php` — tabbed deposit page: Automatic (PayVessel,
  only shown once configured+enabled), Manual Bank Transfer, and Manual
  USDT, each with min/max validation and receipt upload for the manual
  methods
- `payments/pending.php` — live-polling screen showing the generated
  virtual account while waiting for the webhook to confirm payment
  (`ajax/deposit-status.php`)
- `payments/history.php` — the user's own deposit history
- `api/payvessel-webhook.php` — the webhook receiver
- `admin/deposits.php` — approve/reject queue with receipt preview,
  filterable by status; `admin/deposit-settings.php` configures deposit
  limits, manual bank/USDT details, and PayVessel credentials (with the
  webhook URL to paste into the PayVessel dashboard shown inline)

Verified live against MariaDB: manual bank deposit submitted with a real
uploaded receipt → admin-approved → main wallet credited with a correct
ledger entry; a second manual USDT deposit rejected with a reason and no
wallet change; the PayVessel outbound call fails gracefully with a clear
user-facing message in this sandboxed network (no live credentials
available here) — and since PayVessel calls *us*, the webhook receiver
itself was fully verified by crafting real HMAC-SHA512-signed payloads:
a bad signature is rejected (400, no credit), a correctly-signed payload
credits the wallet, replaying the same signed payload is a safe no-op
(idempotency), and a signed payload for an unknown reference is logged
and ignored rather than crashing.

### ✅ Module 7 — Withdrawals
- `wallet_debit_combined()` (`includes/wallet.php`) — a withdrawal draws
  down a user's **combined** balance in priority order (main → bonus →
  referral → mining), not just the main wallet, since bonus/referral/
  mining earnings need to be genuinely withdrawable; each partial debit
  is its own atomic `wallet_debit()` call, with already-debited portions
  credited back if the sequence can't fully cover the amount
- `includes/withdrawals.php` — request creation (validates min/max/daily
  limit/balance, computes the charge, debits immediately at request
  time) and the approve/reject flow; rejecting refunds the full
  requested amount back to the main wallet
- `wallet/bank-accounts.php` — saved bank/USDT withdrawal accounts with
  a default selection
- `wallet/withdraw.php` — request form with a live charge/net-payout
  calculator, plus the user's own withdrawal history
- `admin/withdrawals.php` — approve ("mark paid" — the actual bank/USDT
  transfer happens outside the system) / reject-with-refund queue;
  `admin/withdrawal-settings.php` configures limits, charge percentage
  and the daily request cap

Verified live against MariaDB: funded a user's main/bonus/referral
wallets separately, requested a withdrawal larger than any single
wallet's balance, and confirmed the waterfall correctly drained main
first then spilled into bonus for the remainder (referral untouched);
admin-approved it; a second request correctly spilled into referral too
and was rejected, refunding the exact gross amount into the main wallet;
the daily request limit blocked a further attempt until an admin raised
it; and a withdrawal exceeding the user's total combined balance was
rejected up front with zero wallet mutation.

### ✅ Module 8 — Admin User Management, Mining Plan CRUD & General Settings
- `admin/users.php` — searchable, filterable, paginated user list
- `admin/user-view.php` — edit profile/KYC status, adjust any wallet
  type (credit/debit, reason required and logged), activate/suspend/ban,
  trigger a password reset email, **Login As User** (impersonation), and
  permanently delete a user (cascades their wallet/history via existing
  FK constraints)
- Impersonation banner (`includes/partials/app-head.php`) shown while an
  admin is viewing as a user, with a **Return to Admin** link
  (`user/stop-impersonate.php`) that restores the admin session
- `admin/mining-plans.php` — full CRUD for mining plans (delete is
  blocked once a plan has real positions — deactivate instead) plus
  **Assign Plan to User**, gifting an active position with no wallet
  charge
- `admin/settings.php` — site name/tagline/logo upload, currency,
  timezone, contact info, social links, Google Analytics/Facebook Pixel
  IDs, full SMTP configuration, and maintenance mode; `brand_mark_html()`
  (`includes/functions.php`) swaps in the uploaded logo across the
  landing nav and both sidebars the moment it's set, no code changes
  needed

Verified live against MariaDB: searched/filtered the user list, edited a
profile and KYC status, credited then debited a wallet with the ledger
entries showing up correctly, suspended a user and confirmed login is
blocked, reactivated them, used Login As User and confirmed the
impersonation banner appears and Return to Admin correctly restores the
admin session, created a mining plan, gifted it to a user for free
(position created, wallet untouched), confirmed the delete-guard blocks
removing a plan with active positions, uploaded a custom logo and
confirmed it renders on the landing page and both sidebars, toggled
maintenance mode on/off and confirmed guests are blocked while admins
retain access, triggered a password reset email, and permanently deleted
a user with cascading cleanup confirmed across wallets and mining
positions.

### ✅ Module 9 — Referral System Completion
Modules 1-8 already recorded the referral *relationships* at
registration time, but nothing ever actually paid the bonus out — a real
gap in the earning loop that this module closes:
- `includes/referrals.php` — `referrals_process_deposit_bonus()` walks a
  depositor's upline (from the `referrals` table) and credits each level
  its configured percentage of the deposit into the **referral wallet**,
  logging every payout to `referral_earnings`; wired into
  `deposits_approve()` so it fires automatically whenever a deposit
  (manual or PayVessel) is approved
- `Auth::register()` now actually credits `registration_bonus` (another
  setting that existed but was never applied) to new signups
- `user/referrals.php` — level 1/2/3 referral counts, total referral
  earnings, direct referral list, recent earnings log, and a referral
  leaderboard (top earners platform-wide)
- `admin/referral-settings.php` — configure per-level percentages, levels
  tracked, and the registration bonus, plus platform-wide referral stats

Verified live against MariaDB with a real 3-generation chain (A refers B,
B refers C): confirmed the `referrals` table correctly records A→C as a
level-2 relationship, all three received the registration bonus on
signup, and approving a ₦10,000 deposit from C correctly credited B's
referral wallet 5% (₦500, level 1) and A's referral wallet 2% (₦200,
level 2) with matching `referral_earnings` rows — and the referrals page
and leaderboard both rendered the correct numbers and ranking.

### ✅ Module 10 — Security Audit
A full pass across the codebase looking specifically for the kind of
mistakes that don't show up until someone is actively trying to abuse the
platform:
- `includes/functions.php` — `client_ip()` previously trusted
  `X-Forwarded-For`/`CF-Connecting-IP` headers ahead of `REMOTE_ADDR`,
  which meant any attacker could bypass every IP-based rate limit (login,
  registration, password reset) just by sending a fake header. Now those
  headers are only trusted when the new `TRUST_PROXY_HEADERS` env setting
  is explicitly turned on (for real Cloudflare/reverse-proxy deployments);
  the safe default is the raw TCP connection IP, which a client cannot
  spoof
- `user/register.php` and `ajax/newsletter-subscribe.php` — added
  IP-based rate limiting (reusing the existing `is_rate_limited()` /
  `register_failed_attempt()` mechanism already used for login); verified
  live that exactly 5 attempts are allowed per IP before the 6th is
  blocked
- `includes/security.php` — added a Content-Security-Policy header
  restricting scripts/styles/images/fonts to the app's own origin; a real
  restriction rather than a no-op since every asset is already self-hosted
- `admin/settings.php` — removed SVG from the accepted logo upload
  formats. An SVG is treated as an HTML-like document if it's ever opened
  directly in a browser tab, so it can carry an embedded `<script>` —
  not worth the risk for a logo when PNG/JPG/WEBP cover the same need
- `uploads/.htaccess` — hardened: execution of `.php`/`.phtml`/`.svg`/
  `.html` (etc.) inside the upload directory is now blocked outright,
  `X-Content-Type-Options: nosniff` is sent for everything in the
  directory, and PDFs are forced to download (`Content-Disposition:
  attachment`) instead of rendering inline
- `includes/mailer.php` — every user-supplied value interpolated into an
  HTML email body (name, link, status, title) is now passed through `e()`
  before being placed in the template; the unescaped name is still used
  for PHPMailer's `addAddress()` since that's a MIME header context, not
  HTML
- `admin/mining-plans.php` — replaced a string-concatenated `db()->query()`
  call with a prepared statement (the `$id` was already cast to `int` so
  this wasn't actually exploitable, but it was an inconsistent pattern
  worth closing off)
- `terms.php` / `privacy.php` — replaced `href="javascript:history.back()"`
  with a real `<button>` + `onclick`
- **Critical deployability bug, root `.htaccess`**: `php_flag`/
  `php_value` directives were not wrapped in `<IfModule mod_php.c>`. This
  is invisible when testing against PHP's built-in dev server (which
  ignores `.htaccess` entirely), so it was only caught by actually
  installing Apache in a test environment and enabling mod_rewrite/
  mod_headers/mod_expires: on any host running PHP via php-fpm/suPHP
  instead of mod_php (which includes most modern shared/cPanel hosting),
  an unguarded `php_flag` in `.htaccess` makes Apache fail the **entire
  request** with a 500 error rather than just ignoring the directive.
  Wrapped in the `IfModule` guard, with a comment pointing php-fpm hosts
  at their control panel's PHP settings instead

Verified with a full live regression pass against a fresh MariaDB
instance (registration, login, deposits, mining, referrals all
re-tested end to end to confirm nothing broke), plus a from-scratch
Apache install with a real test vhost to confirm `.htaccess` behavior
under conditions the PHP dev server can't reproduce.

### ✅ Module 11 — Documentation Set
A full `docs/` folder for anyone running or maintaining the platform:
- `docs/ADMIN_GUIDE.md` — every admin panel screen (users, deposits,
  withdrawals, mining plans, tasks, spin wheel, referral settings,
  general settings), written from what each screen actually does today
  rather than the original feature list, including things that turned
  out *not* to exist (there's no in-app "add another admin" screen, and
  no KYC document upload/review queue — KYC is a manual status flag an
  admin sets)
- `docs/CRON_GUIDE.md` — how to schedule `cron/mining-payout.php` on
  cPanel or plain Linux, how to verify it's running, and how it behaves
  after an outage (verified against the actual payout code: it only
  advances `next_payout_at` by one day per run, so a multi-day outage
  catches up gradually over the next several scheduled runs, not all at
  once)
- `docs/PAYVESSEL_INTEGRATION.md` — obtaining credentials, wiring them
  into Deposit Settings vs `.env`, configuring the webhook URL, the full
  request/signature-verification flow, and how to test the signature
  check is actually being enforced
- `docs/API_DOCS.md` — a reference for the internal `ajax/*.php`
  endpoints (auth/CSRF/rate-limit conventions, request/response shape
  per endpoint) and the PayVessel webhook, framed honestly as internal
  endpoints rather than a public third-party API, since none exists

While writing these, found and fixed a stale leftover on the admin
dashboard (`admin/index.php`) claiming that user management, deposit/
withdrawal approval, mining plan management and settings were "being
rolled out in the next build phases" — all of that has existed since
Modules 6-8; the banner was dead scaffold text left behind. Also
corrected several "(upcoming)" folder-structure labels in this README
for modules that have been complete since Module 3.

### ✅ Module 12 — Deposit/Withdrawal CSV Export
`admin/export-deposits.php` and `admin/export-withdrawals.php` stream a
CSV of the full matching record set (unlike the on-screen tables, which
cap at 100 rows) for accounting/reconciliation, honoring the same
status filter as the on-screen list plus an optional `from`/`to` date
range. Both are linked from an "Export CSV" control added next to the
existing status filter buttons on `admin/deposits.php` and
`admin/withdrawals.php`, and every export is written to
`activity_logs`.

Building this against a real PHP 8.4 install (rather than just linting)
caught two real bugs before they shipped:
- `fputcsv()`'s new `$escape` parameter is deprecated-if-omitted as of
  PHP 8.4, and with `display_errors` on that deprecation notice is
  printed as raw HTML *into the CSV response stream itself*, corrupting
  every row. Fixed by passing the delimiter/enclosure/escape arguments
  explicitly on every call.
- Amount columns were originally formatted with `money($amount, false)`,
  which still applies thousands-separator commas (e.g. `15,000.50`) —
  technically valid inside a quoted CSV field, but it silently imports
  as a string rather than a number in Excel/accounting software. Fixed
  to a plain `sprintf('%.2f', ...)`.

Verified live: seeded deposits/withdrawals spanning multiple statuses
and dates against a fresh MariaDB instance, confirmed the status and
date-range filters each return exactly the expected rows, confirmed an
unauthenticated request to either export URL redirects to
`admin/login.php` instead of leaking data, and confirmed the resulting
CSV re-parses cleanly with correct headers and numeric amount columns.

### ✅ Module 13 — Rebrand to SURECASH MINING
Renamed the platform's default brand throughout the codebase (settings
defaults, email templates, install wizard, session/cookie name,
composer package name, default logo/favicon SVGs), and closed a few
real gaps found while doing it:
- `includes/mailer.php` previously hardcoded "9JACASH" in every email
  subject/body instead of reading the configured site name — meant
  renaming the site in Settings never actually changed the emails.
  Added a `Mailer::siteName()` helper and wired every template to it.
- Several partials (`app-sidebar.php`, `admin-sidebar.php`,
  `site-nav.php`, `site-footer.php`, `auth-visual.php`,
  `admin/login.php`) hardcoded the brand name as literal text instead
  of reading the `site_name` setting; switched them to the dynamic
  value so a future rename actually takes effect everywhere.
- `brand_mark_html()`'s fallback glyph (shown when no logo is
  uploaded) was a hardcoded "9"; now derives the first letter of the
  configured site name.
- **Found via live testing, not just linting**: the `<link rel="icon">`
  favicon tag on every single page pointed at
  `assets/images/favicon.svg`, but the actual file has always lived at
  `assets/images/logo/favicon.svg` — a pre-existing 404 since Module 1
  that nothing ever surfaced because a missing favicon just fails
  silently in the browser. Fixed as part of adding
  `favicon_link_html()`, which now also serves the admin's uploaded
  logo as the favicon when one is set.
- Added a sitewide floating WhatsApp contact button
  (`includes/partials/whatsapp-widget.php`) shown on the landing page,
  auth pages and the authenticated app shell — hidden entirely unless
  an admin sets a WhatsApp number in **Admin → Settings**.

### ✅ Module 21 — Website Banner Upload
Added a **Site Banner** upload to Admin → Settings alongside the
existing logo upload (same MIME-sniffed raster-only validation, 3MB
limit), displayed as a full-width strip on the homepage hero when set.

Both modules verified live: registered/logged in a real user, uploaded
a real PNG through the actual admin form (not a direct DB write) for
both the logo and the banner, confirmed the resulting favicon,
sidebar/nav brand mark and homepage banner all serve real 200
responses from the uploaded file paths, and confirmed the floating
WhatsApp button is absent with no number configured and links to the
correct `wa.me/<number>` once one is set.

### ✅ Module 14 — Dual NGN/USD Currency Display
Every wallet balance, deposit, withdrawal and mining plan price stays
stored and ledgered in NGN (the platform's single source of truth) —
this module adds a USD *view* on top of it rather than a second
ledger currency, to avoid the reconciliation headaches of amounts
that could drift mid-transaction against a live market rate:
- **Admin → Settings → Regional** — a manually-set "USD Exchange Rate
  (₦ per $1)" the admin updates whenever they want the displayed
  conversion to move (`includes/functions.php`: `usd_rate()`,
  `to_usd()`, `to_ngn()`, `money_usd()`).
- **Deposit and withdrawal amount fields** now have a linked USD input
  next to the real NGN one — typing in either fills in the other
  live, via a small generic `data-currency-group` / `data-currency-usd`
  pairing helper in `assets/js/main.js`. Only the NGN field is ever
  submitted with the form, so nothing about how deposits/withdrawals
  are processed or stored changed.
- Wallet balance tiles, the dashboard's wallet-balance tile, and mining
  plan prices (landing page + the mining plans page) all show a
  "≈ $X.XX" line under the NGN figure.

Verified live: confirmed the rate is read from settings and exposed to
the page as `window.USD_RATE`, confirmed every USD figure matches
NGN/rate exactly, confirmed the linked deposit/withdrawal fields exist
on every method tab and only appear on the withdrawal page once the
user has an account to withdraw to, and confirmed the existing
withdrawal charge/net calculation still fires correctly when the
amount is entered via the USD field.

### ✅ Module 15 — User/Admin Support Messaging Inbox
A real two-way dashboard-to-dashboard chat, distinct from the existing
one-way public contact form (`ajax/contact-submit.php`, still intact
for logged-out visitors):
- `support_messages` table — one running conversation thread per user,
  every row scoped by `user_id` regardless of which side sent it, with
  independent `is_read_by_user`/`is_read_by_admin` flags
- `includes/support.php` — send/reply/read-tracking/unread-count
  functions, plus `support_conversation_list_for_admin()` for the inbox
- `user/messages.php` — a chat-style thread view + reply box; visiting
  it marks every admin message read
- `admin/messages.php` — an inbox listing every user who has messaged
  in, ordered by most recent activity with an unread badge per
  conversation, click through to view/reply to any thread
- Unread badges on both the user sidebar ("Messages") and admin
  sidebar, matching the existing pending-deposits/withdrawals badge
  style; an admin reply also fires an in-app notification to the user

Found and fixed a real bug while testing the very first live reply:
`notifications.type` is a MySQL `ENUM` that didn't include `'support'`,
so the first admin reply saved to `support_messages` correctly but
then fatally errored on the notification insert
(`SQLSTATE[01000]: Data truncated for column 'type'`) instead of
failing gracefully. Added `'support'` to the enum.

Verified live end-to-end: sent a message as a real logged-in user,
confirmed it appeared unread in the admin inbox with the correct
sidebar badge count, replied as admin, confirmed the user received an
in-app notification and saw the reply, and confirmed both sides'
unread badges correctly cleared after viewing (not before).

### ✅ Module 17 — Dashboard Earnings Fix
`user/dashboard.php`'s "Total Earnings" and "Today's Earnings" tiles
summed *every* credit-type `wallet_ledger` row, which included
deposits (a deposit is a real wallet credit, but it's not something
the user *earned*). Restricted both queries to
`source IN ('mining','task','ad','spin','checkin','referral')`,
excluding deposits, withdrawal refunds, admin adjustments and internal
transfers.

Verified live: seeded a user with a ₦50,000 deposit plus ₦500 mining +
₦300 task earnings today and ₦400 mining earnings from yesterday —
confirmed Total Earnings showed exactly ₦1,200 (500+300+400, deposit
correctly excluded) and Today's Earnings showed exactly ₦800 (500+300,
yesterday's mining correctly excluded too).

### ✅ Module 16 — Mining Day-Cycle Selection
Users purchasing a mining plan now choose how many days to run it
(7/14/21/30, admin-configurable per plan) rather than every position
being locked to the plan's single fixed duration:
- `mining_plans.available_cycles` — a comma-separated subset of
  7/14/21/30 the admin enables per plan (`admin/mining-plans.php`
  checkboxes); the existing `duration_days` column becomes the
  reference duration used only for admin-gifted positions
- `includes/mining.php`: `mining_plan_cycles()` reads/validates a
  plan's enabled cycles; `mining_purchase_plan()` now takes a
  `$cycleDays` argument, validated server-side against the plan's
  enabled list before computing `ends_at` — same `daily_return` rate
  regardless of the cycle chosen (per design), so total earnings simply
  scale with the number of days picked
- `mining/invest.php` — radio buttons for each enabled cycle with a
  live-updating total-return figure
- Plan cards (mining page + landing page) show "Choose 7, 14, 21, 30
  days" and an "Up to ₦X total" figure (computed off the longest
  enabled cycle) instead of a single fixed duration/total, since the
  real total now depends on what the user picks at purchase time

Mining payouts already drew from the combined withdrawal wallet
waterfall (`WALLET_MINING` is in `WALLET_WITHDRAW_PRIORITY`), so "let
daily payout go to wallet balance for withdrawal" was already true —
verified rather than changed.

Verified live: purchased a 7-day cycle on a plan and confirmed the
resulting position's `ends_at` was exactly 7 days after `started_at`
(not the plan's 30-day default); edited a plan's admin settings to
only offer 14/30-day cycles and confirmed the invest page immediately
reflected only those two options and the server rejected a
since-disabled 7-day submission.

### ✅ Module 20 — Play Store / App Store Download Links
Admin-configurable Play Store and App Store URLs (**Admin → Settings
→ Mobile App Links**), shown as download badges on the homepage hero
and at the top of the user dashboard. Each badge is independently
optional — `includes/partials/app-download-badges.php` renders
nothing at all if neither URL is set, and only the configured badge(s)
if just one is.

Verified live: confirmed no badges render with both URLs empty
(the seeded default), and confirmed both badges render with the
correct `href` on both the homepage and the dashboard once set.

### ✅ Module 19 — Spin Wheel Paid Extra Spins
Once a user exhausts their free daily spin(s), they can now buy
additional spins from their combined wallet balance:
- **Admin → Spin Wheel Settings** — an "Extra spin price" field
  alongside the existing daily-limit setting, server-side clamped to a
  ₦50 minimum regardless of what's typed in
- `includes/spin.php`: `spin_play()` takes a `$paid` flag that bypasses
  the daily-limit check; `spin_buy_extra_and_play()` debits the price
  via the existing combined-wallet waterfall (`wallet_debit_combined()`
  — main, then bonus, referral, mining) and immediately plays a spin,
  refunding automatically if the spin itself can't proceed (e.g. no
  active segments configured) so a user is never charged for nothing
- `ajax/spin-buy-extra.php` — mirrors the existing `spin-play.php`
  endpoint's auth/CSRF conventions
- `spin/index.php` shows a "Buy Extra Spin (₦X)" button once the free
  daily spin is used, disabled with a clear reason if the user's
  combined balance can't cover it; after a paid spin the page reloads
  so the button re-evaluates against the user's new balance (letting
  them buy another right away if they can still afford it)

Also fixed several more leftover `NineJaCash` references in inline
`<script>` blocks (`wallet/index.php`, `payments/pending.php`,
`spin/index.php`, `ads/index.php`) that Module 13's rebrand pass had
missed — it only caught the standalone `.js` files, not inline script
blocks inside PHP templates.

Verified live: used the free daily spin, confirmed the page switched
to the paid "Buy Extra Spin" button, bought one and confirmed the
exact price was debited and a second `spin_logs` row was created, then
set the wallet below the price and confirmed the purchase was rejected
server-side with the wallet balance completely unchanged (no partial
charge).

### Planned next
Branding asset pack (PNG exports, social banner, app icon) →
transaction receipts → a modern visual redesign with
conversion-focused landing page elements.

## License

Proprietary — all rights reserved.
