# SURECASH MINING — PayVessel Integration Guide

PayVessel is the automatic bank-transfer deposit option: a user requests
a deposit, the platform asks PayVessel for a one-time ("DYNAMIC")
reserved account number, the user transfers into that account from their
own bank, and PayVessel notifies the platform by webhook the moment the
transfer settles — crediting the user's wallet with no admin action
needed.

Manual bank/USDT deposits (reviewed by an admin) work independently and
don't require any of this — PayVessel is purely the automated path.
Reference: https://payvessel.gitbook.io/payvessel/

## 1. Get your PayVessel credentials

Sign up for a PayVessel business account and obtain, from your PayVessel
dashboard:

- **Public key** (`api-key` header on requests)
- **Secret key** (`api-secret` header, and also used to verify webhook
  signatures — keep this one especially private)
- **Business ID**
- The **bank code(s)** you want reserved accounts issued against (ask
  PayVessel which banks are available to you; the platform defaults to
  `120001` if you don't set any)

## 2. Enter them in the admin panel

Go to **Admin → Deposit Settings** (`admin/deposit-settings.php`) and:

1. Check **Enable automatic PayVessel deposits**.
2. Paste in the Public Key, Secret Key, Business ID, and bank code(s).
3. Save.

These are stored in the `site_settings` table via `get_setting()`/
`set_setting()`, not in `.env` — so you can rotate keys without editing
files on the server. (The `.env` file also has `PAYVESSEL_*` variables;
they only act as a fallback default if nothing is set in the admin
panel — whatever you enter in Deposit Settings takes priority.)

If you'd rather configure via environment variables only (e.g. for a
staging environment where you don't want secrets in the database), set:

```
PAYVESSEL_PUBLIC_KEY=
PAYVESSEL_SECRET_KEY=
PAYVESSEL_BUSINESS_ID=
PAYVESSEL_BASE_URL=https://api.payvessel.com
PAYVESSEL_BANK_CODES=120001
```

in `.env` and simply leave the admin-panel fields blank.

## 3. Configure the webhook in your PayVessel dashboard

Deposit Settings displays the exact webhook URL to paste into PayVessel:

```
https://yourdomain.com/api/payvessel-webhook.php
```

This must be reachable over the public internet with a valid HTTPS
certificate — PayVessel calls it server-to-server, so it cannot reach a
`localhost` or an internal/staging URL that isn't publicly resolvable.

## 4. How it works end to end

1. A user chooses PayVessel at the deposit screen. `PayVessel::createVirtualAccount()`
   calls `POST /pms/api/external/request/customerReservedAccount/` with
   the user's email, name and phone, and gets back a one-time account
   number + `trackingReference`.
2. The user transfers the deposit amount into that account from any
   Nigerian bank.
3. PayVessel settles the transfer and calls your webhook with a JSON
   payload containing the transaction reference and settlement amount,
   signed with `HMAC-SHA512` over the raw request body using your secret
   key, sent in the `Payvessel-Http-Signature` header.
4. `api/payvessel-webhook.php` verifies that signature with
   `hash_equals()` before trusting anything in the payload — **a request
   with a missing or invalid signature is rejected with a 400 and never
   reaches deposit-crediting logic**, regardless of what its body claims.
5. On a verified payload, `deposits_handle_payvessel_notification()`
   credits the matching deposit and pays out referral bonuses, exactly
   like an admin-approved manual deposit.

The webhook also logs (but does not block on) whether the calling IP
matches PayVessel's documented source IPs (`3.255.23.38`,
`162.246.254.36`) — this is defense-in-depth only, since shared hosting
behind a proxy/CDN often can't see the true caller IP. The HMAC
signature, not the IP, is what actually authenticates the request.

## 5. Testing

PayVessel provides a sandbox/test mode — check their dashboard for test
credentials and a way to simulate a settlement notification. Before
going live:

- Confirm a real reserved account number is generated and displayed to
  a test user.
- Send a manually-crafted request to your webhook with a **wrong**
  signature and confirm you get back `{"message":"Permission denied,
  invalid hash."}` with HTTP 400 — this is your evidence the signature
  check is actually being enforced, not silently bypassed.
- Complete an actual (or sandbox) transfer and confirm the wallet is
  credited automatically without any admin approval step.

## 6. Troubleshooting

- **"Automatic bank transfer is not configured yet"** shown to users —
  `PayVessel::isConfigured()` requires all three of public key, secret
  key and business ID to be non-empty (from either the admin panel or
  `.env`); fill in whichever is missing.
- **Webhook calls return 400 "invalid hash"** — almost always a secret
  key mismatch between what's in your PayVessel dashboard and what's
  saved in Deposit Settings. Re-copy the secret key carefully (no
  leading/trailing whitespace).
- **Deposits aren't auto-crediting even though the transfer went
  through** — check `logs/app-*.log` for `PayVessel webhook` entries;
  every webhook call is logged with its outcome, whether rejected for a
  bad signature or processed successfully.
