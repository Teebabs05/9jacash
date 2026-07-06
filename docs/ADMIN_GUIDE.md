# SURECASH MINING — Admin Guide

This guide covers everything an administrator needs to operate the
platform day to day: logging in, approving money movements, managing
users, and configuring the settings that drive earning features.

## 1. Logging in

Admin login is a separate area from the user-facing site:

```
https://yourdomain.com/admin/login.php
```

The installer creates the first admin account with the username and
password you set during setup (the default seed used in development is
`admin` / `1988125012` — **change this password immediately** on a real
deployment, from `admin/settings.php` is not where admin passwords are
changed today; use the database or add yourself a new admin row via SQL
if you need a second admin account, since there is currently no
in-app "add admin" screen).

Once logged in you land on `admin/index.php`, a dashboard showing:

- Total registered users
- Pending deposits awaiting approval
- Pending withdrawals awaiting approval
- Active mining positions
- Task submissions awaiting review

## 2. Users (`admin/users.php`, `admin/user-view.php`)

- **List & search** every registered user, with their wallet balance,
  KYC status and account status (active/suspended) at a glance.
- **View a user** to see their full profile, wallet balances, referral
  info and recent activity.
- **Edit profile fields and KYC status** (pending/approved/rejected) —
  this is a manual status flag an admin sets after reviewing whatever
  identity evidence the user has sent you outside the platform (there is
  no in-app document upload/review queue at this time).
- **Suspend / reactivate** an account.
- **Login As User** ("impersonate") — opens the platform as that user so
  you can see exactly what they see, useful for support/debugging.
  Click **Return to Admin** (or visit `user/stop-impersonate.php`) to
  exit back to your own admin session; this is logged in
  `activity_logs` under `admin_login_as` / `admin_login_as_ended`.
- **Trigger a password reset email** on the user's behalf.
- **Delete a user** — this cascades and cleans up their wallets and
  mining positions. This is permanent; there is no undo.

## 3. Deposits (`admin/deposits.php`)

Two deposit paths land here:

- **Manual bank/USDT transfers** — the user submits a reference/proof,
  and you approve or reject after checking your bank/wallet for the
  matching transfer.
- **PayVessel automatic transfers** — these are normally approved
  automatically by the webhook the moment PayVessel confirms settlement
  (see the PayVessel Integration Guide), so you'll mostly see these
  already marked approved. They still appear here for visibility.

Approving a deposit:
1. Credits the user's main wallet for the deposit amount.
2. Pays referral bonuses up the chain (see `referral-settings.php`
   below) if the user was referred.
3. Sends the user a deposit-confirmation email.

Rejecting a deposit leaves the user's wallet untouched and records the
rejection reason you enter.

Configure deposit rules from **Deposit Settings** (`admin/deposit-settings.php`):
minimum/maximum deposit amount, manual bank account details shown to
users, USDT address/network, and the PayVessel credentials (see the
PayVessel guide for how to obtain and enter these).

**Export CSV** next to the status filter downloads every deposit
matching the current status (and an optional date range you pick before
exporting) as a CSV — unlike the on-screen table, the export is not
capped at 100 rows, so it's suitable for full accounting reconciliation.

## 4. Withdrawals (`admin/withdrawals.php`)

All withdrawals require manual admin approval — there is no automatic
payout integration. For each pending request you can:

- **Approve** — marks it paid. You are responsible for actually sending
  the funds to the user's bank/USDT details *outside* the platform
  before approving (approval does not move real money anywhere; it only
  updates the ledger and notifies the user).
- **Reject** with a note — the withdrawal amount is refunded back to the
  user's wallet automatically.

Configure withdrawal rules from **Withdrawal Settings**
(`admin/withdrawal-settings.php`): minimum/maximum withdrawal, daily
withdrawal limit per user, and the withdrawal charge percentage.

Withdrawals have the same **Export CSV** control (status + optional
date range, uncapped row count) as Deposits above.

## 5. Mining Plans (`admin/mining-plans.php`)

Create/edit/deactivate mining plans: price, daily return amount,
duration in days, description, and active/inactive status. Deactivating
a plan stops new purchases but does not affect users who already hold a
position on it. A plan with existing positions can't be deleted (the
admin panel checks `user_mining` before allowing deletion) — deactivate
it instead.

Daily payouts are **not** processed by anything in the admin panel — see
the Cron Guide for the scheduled job that actually pays out active
mining positions.

## 6. Tasks (`admin/tasks.php`, `admin/task-submissions.php`)

- **Tasks** — create social/website tasks (e.g. "Follow us on X",
  "Join our Telegram") with a reward amount and instructions.
- **Task Submissions** — review proof submitted by users for each task
  and approve (credits the reward to their wallet) or reject.

## 7. Spin Wheel (`admin/spin-settings.php`)

Configure the wheel's segments: label, cash amount, color, and relative
probability weight (out of 100 across all active segments), plus the
daily spin limit per user (`spin_daily_limit` under General Settings).

## 8. Referral Settings (`admin/referral-settings.php`)

- Per-level payout percentages (levels 1–3 by default) applied to a
  user's deposit and credited to their upline's **referral wallet**.
- How many levels deep the referral tree pays out
  (`referral_max_levels`).
- The flat registration bonus paid to every new signup
  (`registration_bonus`).
- Platform-wide referral stats: total paid out, top earners.

## 9. General Settings (`admin/settings.php`)

Covers everything else:

- **Branding** — site name, tagline, logo upload (PNG/JPG/WEBP only —
  see the Security Audit notes in the README for why SVG isn't
  accepted), currency and symbol, timezone.
- **Contact & social links** — support email/phone, Facebook/Twitter/
  Instagram/WhatsApp/Telegram URLs, shown in the site footer.
- **Analytics** — Google Analytics ID, Facebook Pixel ID.
- **Mail (SMTP)** — host, port, encryption, username/password, from
  address/name. Used by `includes/mailer.php` for every transactional
  email (verification, welcome, password reset, deposit/withdrawal
  notifications).
- **Ads (watch-to-earn)** — reward per ad, watch duration required
  before the reward can be claimed, cooldown between ads, and the daily
  ad limit per user.
- **Daily check-in** — the base reward amount (the streak bonus scaling
  is computed in code from this base, not separately configurable).
- **Maintenance mode** — toggling this on immediately blocks every
  non-admin visitor with a 503 and your custom message, while admins
  (and the `/admin/` area itself) keep working normally so you can still
  fix whatever needs fixing.

## 10. Notifications

Users receive in-app notifications and emails for account and wallet
events (deposit approved/rejected, withdrawal approved/rejected, task
submission reviewed, etc.) automatically as part of the relevant action
above — there is no separate "compose a broadcast" screen in this
build.

## 11. Activity Logs

Every admin action that changes state (approvals, rejections, profile
edits, impersonation, deletions) is written to `activity_logs` with the
admin's ID attached, so you always have an audit trail of who did what.
