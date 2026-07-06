# SURECASH MINING — Internal Endpoint Reference

SURECASH MINING does not expose a public developer API for third-party
integrations. This document describes the two kinds of HTTP endpoints
that exist internally, for anyone maintaining or extending the codebase:

1. **AJAX endpoints** (`ajax/*.php`) — called by the platform's own
   frontend JavaScript, authenticated by the user's session and
   protected by CSRF tokens. Not meant to be called by anything else.
2. **The PayVessel webhook** (`api/payvessel-webhook.php`) — the one
   endpoint designed to be called by an external server. See
   `PAYVESSEL_INTEGRATION.md` for full details; it's only summarized
   here for completeness.

None of these require an API key or issue one — there's no bearer-token
or OAuth layer in this build. Authentication is entirely the standard
PHP session cookie set at login.

## Conventions shared by every `ajax/*.php` endpoint

- Responds `Content-Type: application/json`.
- Requires an active logged-in session unless noted otherwise — returns
  HTTP 401 with `{"success":false,"message":"Please log in again."}` if
  not.
- Requires `POST` — returns HTTP 405 for any other method.
- Requires a valid CSRF token, sent either as a `csrf_token` POST field
  or an `X-CSRF-Token` header — returns HTTP 419 if missing/invalid.
  Every authenticated page renders the current token into
  `<meta name="csrf-token">` for JavaScript to read.
- On success, returns a JSON object; the exact shape is per-endpoint
  (documented below), but every one of them at least includes a
  `success` boolean (except `deposit-status.php`, which is a simple
  polling endpoint — see below).

## `POST /ajax/ads-start.php`

Starts a watch-to-earn ad session for the logged-in user.

- Checks `ads_can_watch()` first (daily limit / cooldown from settings)
  and returns `{"success":false,"message":"..."}` with the reason if the
  user isn't currently eligible.
- On success, stores a one-time watch token + start timestamp in the
  session (`$_SESSION['ad_watch']`) and returns it to the client so the
  page can start its countdown/player UI.

## `POST /ajax/ads-claim.php`

Claims the reward for a previously-started ad watch.

- Body: `watch_token` — must match the token issued by `ads-start.php`
  (compared with `hash_equals()`).
- Rejects the claim if the configured watch duration
  (`ad_watch_duration_seconds`) hasn't actually elapsed since the
  session's start timestamp — this is enforced server-side against
  server time, not the client's claimed elapsed time, so a client can't
  just wait 0 seconds and claim instantly.
- On success, credits `ad_reward_amount` to the user's wallet and
  returns the updated state.

## `POST /ajax/spin-play.php`

Plays one spin of the daily spin wheel.

- Returns `{"success":false,"message":"You have already used your daily
  spin. Come back tomorrow!"}` if the user already spun today.
- Picks a weighted-random active segment from `spin_settings`
  (probability is a weight out of 100 across active segments) and
  credits its `amount` to the user's wallet.
- On success, returns which segment won so the frontend can animate the
  wheel to land on it.

## `GET /ajax/deposit-status.php?id={depositId}`

Lightweight polling endpoint used by the deposit page to check whether a
pending deposit has been approved/rejected yet without a full page
reload.

- The only endpoint here that's a `GET` and doesn't require a CSRF
  token, since it only reads data scoped to the logged-in user's own
  deposit (`WHERE id = ? AND user_id = ?` — a user can't query another
  user's deposit by guessing its ID).
- Returns `{"status": "pending"|"approved"|"rejected"|"unknown"}`.

## `POST /ajax/contact-submit.php`

Public contact form submission — does **not** require login. Requires
CSRF (the token is rendered into the public contact page too) and is
rate-limited per IP (`contact:{ip}`). Validates name/email/subject/
message and inserts into `contact_messages`.

## `POST /ajax/newsletter-subscribe.php`

Public newsletter signup — does not require login. Requires CSRF,
rate-limited per IP (`newsletter:{ip}`). Validates the email and inserts
into `newsletter_subscribers` (an existing email is silently treated as
already-subscribed via `ON DUPLICATE KEY UPDATE email = email` rather
than erroring).

## `POST /api/payvessel-webhook.php` (external, server-to-server)

Called by PayVessel, not by the platform's own frontend — no session,
no CSRF token, no login. Authenticity is instead established by
verifying an `HMAC-SHA512` signature (`Payvessel-Http-Signature` header)
computed over the raw request body using your PayVessel secret key.
Requests with a missing/invalid signature get HTTP 400 and are never
processed. Full details, payload shape, and setup steps are in
`PAYVESSEL_INTEGRATION.md`.
