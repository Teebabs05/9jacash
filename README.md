# 9JACASH

A complete, secure, production-ready earning platform — mining plans, micro-tasks,
multi-level referrals, daily rewards, deposits/withdrawals with PayVessel and manual
bank transfer, and a full admin console. Built as an original PHP 8.2 MVC application
(no framework dependency) with a Bootstrap 5 fintech-style front end.

> **Legal note:** this codebase is provided as a technical foundation for a legitimate
> rewards/affiliate platform. If you launch a site that holds user deposits or pays out
> withdrawals, you are responsible for complying with the payment gateway's terms and
> your local financial/consumer-protection regulations (in Nigeria, this may include
> CBN and SEC guidance depending on how "earnings" are structured). Have the Terms of
> Service and Privacy Policy pages reviewed by a qualified lawyer before going live.

---

## 1. Tech Stack

| Layer      | Technology |
|------------|------------|
| Backend    | PHP 8.2+, PDO (MySQL), custom lightweight MVC (no Composer required) |
| Frontend   | HTML5, Bootstrap 5, vanilla JS + AJAX (fetch), SweetAlert2, Chart.js, Font Awesome |
| Database   | MySQL 8 / MariaDB 10.4+ |
| Mail       | Dependency-free SMTP client (falls back to PHP `mail()`) |
| Payments   | PayVessel (instant) + manual bank transfer with receipt upload |

No Composer/vendor directory is required — the app ships with its own PSR-4-style
autoloader, so it can be uploaded as-is to shared hosting (cPanel, LiteSpeed, Apache).

---

## 2. Directory Structure

```
9jacash/
├── app/
│   ├── Controllers/        Auth, User, Admin, Api controllers (PSR-4 autoloaded)
│   ├── Core/                Router, Database (PDO), Session, Security, Auth, Mailer, Totp, Upload...
│   ├── Models/               One class per table
│   ├── Services/            Cross-cutting business logic (wallet ledger, referrals, mining payouts...)
│   ├── views/                 PHP templates (layouts/partials/pages/auth/user/admin)
│   ├── config/config.php     Reads .env into a config array
│   ├── routes.php            All route definitions
│   ├── helpers.php           Global helper functions (view(), e(), money(), setting()...)
│   └── bootstrap.php         Front-controller bootstrap
├── public/                   Web root — point your document root here
│   ├── index.php             Front controller
│   ├── install/              Web installer (self-deletes access after install)
│   └── assets/               CSS / JS / images
├── database/
│   ├── schema.sql            Full schema
│   └── seed.sql              Default admin + sample settings/plans/tasks
├── cron/
│   └── mining_earnings.php   Hourly payout job (CLI)
└── storage/                  Logs + uploaded files (outside the web root)
```

### Security-relevant design choices

- **Document root should be `public/`.** `app/`, `database/`, `storage/` and `.env`
  stay outside the web root entirely. A fallback `.htaccess` at the project root
  rewrites into `public/` for hosts where you cannot change the document root
  (e.g. some cPanel addon domains) — but changing the document root is preferred.
- **Uploads never land in `public/`.** Receipts, KYC documents and task proofs are
  stored under `storage/uploads/` and served through `FileController`, which checks
  session ownership (or admin role) before streaming the file. Avatars are treated
  as low-sensitivity and served to any logged-in user.
- **All queries use PDO prepared statements** (`App\Core\Database`), with
  `PDO::ATTR_EMULATE_PREPARES` disabled so parameters are always sent as data, never
  interpolated into SQL.
- **CSRF**: every state-changing form includes a per-session token
  (`Security::csrfField()` / `Controller::verifyCsrf()`).
- **XSS**: all dynamic output goes through `e()` (htmlspecialchars); user input is
  also stripped of tags on the way into the database via `sanitize()`.
- **Passwords**: bcrypt via `password_hash()`/`password_verify()`.
- **Rate limiting**: login, password reset, email-verification resend, and the
  public contact form are all rate-limited per identifier+IP (`App\Core\RateLimiter`).
- **2FA**: optional TOTP (Google Authenticator-compatible), implemented locally
  with no external dependency.
- **Sessions**: HTTP-only, `SameSite=Lax` cookies, periodic ID regeneration, idle
  timeout, and forced regeneration on login/impersonation.
- **Activity & IP logging**: `activity_logs` records login/logout, admin actions,
  deposits/withdrawals, KYC events, etc. with IP address and coarse device type.

---

## 3. Installation

### Option A — Web installer (recommended)

1. Upload the contents of this repository to your server so that your web server's
   document root points at the **`public/`** folder.
2. Visit `https://yourdomain.com/install/` in a browser.
3. The wizard will:
   - Check PHP version/extensions and folder permissions.
   - Create the database (if it doesn't exist) and import `database/schema.sql` +
     `database/seed.sql`.
   - Write your `.env` file with a freshly generated `APP_KEY` and `CRON_SECRET`.
4. Log in at `/login` with the default administrator account:

   ```
   Username: admin
   Password: 1988125012
   ```

   You will be **forced to set a new password** on first login.
5. Delete access to `/install/` is automatic — the wizard writes
   `public/install/installed.lock` and refuses to run again while that file exists.

### Option B — Manual setup

```bash
cp .env.example .env
# edit .env with your DB credentials, mail, PayVessel, reCAPTCHA settings

mysql -u youruser -p -e "CREATE DATABASE 9jacash CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u youruser -p 9jacash < database/schema.sql
mysql -u youruser -p 9jacash < database/seed.sql
```

Point your web server's document root at `public/`, make sure `storage/` is
writable by the web server user, and you're done.

### Local development server

```bash
cd public
php -S localhost:8000
```

---

## 4. Cron Setup (mining payouts)

Mining plans pay out once every ~23 hours per purchase. Set up **one** of the
following:

**Preferred — real CLI cron (cPanel → Cron Jobs, or crontab -e):**

```
0 * * * * /usr/bin/php /home/youruser/9jacash/cron/mining_earnings.php >> /home/youruser/9jacash/storage/logs/cron.log 2>&1
```

**Fallback — HTTP-triggered cron** for hosts without shell/cron access (use your
host's "cron via URL" feature, or an external pinger like cron-job.org):

```
https://yourdomain.com/cron/mining-earnings?token=YOUR_CRON_SECRET
```

`YOUR_CRON_SECRET` is generated automatically during installation and stored as
`CRON_SECRET` in `.env`.

---

## 5. PayVessel Integration

Configure your PayVessel public/secret keys and webhook secret under
**Admin → Settings → PayVessel** (or directly in `.env`).

- **Deposit flow**: `User\DepositController::payvesselInit()` creates a `pending`
  deposit row with a unique reference, calls `PayVesselService::initializePayment()`,
  and redirects the user to the returned checkout URL.
- **Webhook**: configure PayVessel's dashboard to call
  `POST https://yourdomain.com/api/webhook/payvessel`. The handler
  (`Api\PayVesselController`) verifies an HMAC-SHA512 signature against your webhook
  secret, logs every payload to `webhook_logs` (valid or not), and only credits the
  wallet once — deposits are matched by reference and are idempotent (`DepositService`
  refuses to process a deposit that isn't still `pending`).
- **Browser callback**: `deposit/payvessel/callback` re-verifies the transaction
  server-side (never trusts query params alone) as a UX-level fallback in case the
  webhook is delayed.

> PayVessel's exact endpoint paths can change and are gated behind live/sandbox
> credentials — verify `App\Services\PayVesselService` against PayVessel's current
> API docs before going live. Everything else (webhook signature verification,
> wallet crediting, referral bonus distribution) is provider-agnostic and does not
> need to change if you swap gateways.

### Manual bank deposits

Configure one or more bank accounts under **Admin → Payment Methods**. Users upload
a receipt image, which goes into an admin approval queue
(**Admin → Deposits**) before the wallet is credited.

---

## 6. Configuring Email (SMTP)

Set SMTP host/port/username/password under **Admin → Settings → SMTP / Email** (or
in `.env`). If left blank, the app falls back to PHP's built-in `mail()` function.
The mailer is a small dependency-free SMTP client (`App\Core\Mailer`) supporting
STARTTLS/SSL and AUTH LOGIN — no Composer/PHPMailer required.

## 7. reCAPTCHA

Add your site/secret key under **Admin → Settings → reCAPTCHA** and toggle it on.
When enabled, it's verified server-side during registration.

---

## 8. Default Admin & Security Checklist Before Going Live

- [ ] Log in as `admin` / `1988125012` and set a new password (forced automatically).
- [ ] Enable 2FA on the admin account (**Profile → Two-Factor Authentication**).
- [ ] Configure SMTP so email verification/password reset actually deliver.
- [ ] Configure PayVessel keys + webhook secret, or disable it and rely on manual deposits.
- [ ] Set real min/max deposit & withdrawal limits and charges under **Admin → Settings**.
- [ ] Decide whether KYC is required (**Admin → Settings → General**) before withdrawals.
- [ ] Set up the mining payout cron (Section 4).
- [ ] Enable HTTPS and uncomment the HTTPS redirect in `public/.htaccess`.
- [ ] Review `terms`/`privacy` page copy with legal counsel — the shipped copy is a
      starting template, not legal advice.

---

## 9. Wallet & Ledger Model

Each user has one `wallets` row split into five purses: `main`, `bonus`, `referral`,
`mining`, `task`. Only `main_balance` is directly withdrawable — earnings in the
other purses are moved into `main` by the user via **Wallet → Transfer** before
cashing out. Every credit/debit is written to the `transactions` table as an
immutable ledger row (`App\Models\Wallet::credit()`/`debit()`), so balances can
always be reconciled from history.

Withdrawal funds are debited from `main` the moment a request is submitted (so a
user can never request more than they have while a request is pending); rejecting
a withdrawal refunds the full amount automatically.

## 10. Referral Engine

Referral commissions are distributed by walking the `referred_by` chain
(`App\Models\ReferralEarning::distribute()`), with per-level percentages read from
the `settings` table (**Admin → Referral Settings**): level 1 uses a
source-specific percent (`referral_deposit_percent`, `referral_mining_percent`,
`referral_task_percent`), levels 2/3 use flat settings, and any level with a 0%/
unset value simply stops the chain — so you can extend beyond 3 levels by adding
`referral_level_4_percent`, etc. without a code change. Signup bonuses are a flat
amount rather than a percentage (there's no "deposit" to take a cut of yet).

## 11. Known Scope Notes

This is a very large feature set; a few items were deliberately kept simple rather
than over-engineered, and can be extended without restructuring anything:

- **Single currency** (NGN by default, configurable symbol/code) — no live FX
  conversion. Multi-currency would need a rates table + display-layer conversion.
- **Badges/achievements** are not a separate admin-managed CRUD; the leaderboard
  and streak counters are computed live from existing tables.
- **Language switcher**: the UI is English-only; no i18n string table is wired up.
- **QR referral codes** use a public QR-image API (`api.qrserver.com`) client-side —
  swap for a local QR library if you need to avoid third-party requests.

---

## 12. Support / Tickets

Users can open tickets from **Support**, attach an image, and reply on the thread.
Admins reply from **Admin → Support Tickets**; both sides get in-app notifications.

## 13. License

Provided as a custom build for the requesting project. Adapt freely for your own
deployment; no attribution required.
