# SURECASH MINING — Cron Job Guide

The platform has exactly one scheduled job. Everything else (referral
bonuses, task/ad/spin/check-in rewards) is credited synchronously the
moment the user performs the action or an admin approves it — nothing
else needs a scheduler.

## Mining payouts (`cron/mining-payout.php`)

Mining plans pay out daily, but nothing pays them automatically until
this script runs. It:

1. Finds every `user_mining` position whose `next_payout_at` has
   elapsed.
2. Credits that position's `daily_return` to the user's main wallet.
3. Advances `next_payout_at` by 24 hours.
4. Marks the position `completed` once it has been paid out for its
   full `duration_days`, so it stops accruing further.

It is a CLI-only script — it refuses to run if requested over HTTP, so
there's no way to trigger it by just visiting a URL.

### Setting it up

It's safe to run as often as your host allows; hourly is a sensible
default (it only pays out positions that are actually due, so running it
more often than once a day does not overpay anyone).

**cPanel ("Cron Jobs" under Advanced):**

```
0 * * * * /usr/bin/php /home/USERNAME/public_html/cron/mining-payout.php >> /home/USERNAME/public_html/logs/cron.log 2>&1
```

Replace `USERNAME` and the path with your actual home directory and
document root. If you're unsure of the PHP binary path, cPanel usually
lists version-specific binaries like `/usr/local/bin/php` or
`/opt/cpanel/ea-php82/root/usr/bin/php` — check "Select PHP Version" or
ask your host; using the wrong PHP version can subtly break things if it
doesn't match what you configured for the web-facing site.

**Plain Linux server (`crontab -e`):**

```
0 * * * * /usr/bin/php /var/www/surecash-mining/cron/mining-payout.php >> /var/www/surecash-mining/logs/cron.log 2>&1
```

### Verifying it's working

- Check `logs/cron.log` (or wherever you redirected output) after the
  first run — each run appends a line like:
  ```
  [2026-01-15 09:00:02] Mining payout run: 3 position(s) paid, 1 completed, ₦4,500.00 total credited.
  ```
- The same line is also written to the app's own log
  (`logs/app-*.log`) via `app_log()`, so it shows up alongside every
  other application log entry too.
- If a user's mining position isn't paying out, check its
  `next_payout_at` value directly in the `user_mining` table — if it's
  in the future, it simply isn't due yet.

### What happens if the cron job doesn't run for a while

Nothing is lost, but catch-up is gradual rather than instant: each run
only advances a position's `next_payout_at` by one day, even if it's
been overdue for longer than that. So if the cron was down for 3 days,
a position doesn't get 3 days credited in the next run — it gets 1 day
credited per subsequent run (still counted as "due" as long as
`next_payout_at` remains in the past), fully catching up over the next
few scheduled runs rather than all at once. With an hourly schedule this
resolves within a few hours of the job resuming; the only real risk is
leaving the job disabled for so long that users notice the delay.
