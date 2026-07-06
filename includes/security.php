<?php
/**
 * CSRF protection, brute-force rate limiting and misc security helpers.
 */

declare(strict_types=1);

/**
 * Return the current CSRF token, generating one if it doesn't exist yet.
 */
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = generate_token(32);
        }

        return $_SESSION['_csrf_token'];
    }
}

/**
 * Render a hidden CSRF input field.
 */
if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
    }
}

/**
 * Verify a submitted CSRF token, terminating the request on failure.
 */
if (!function_exists('verify_csrf')) {
    function verify_csrf(?string $token): bool
    {
        if (!$token || empty($_SESSION['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'], $token)) {
            app_log('warning', 'CSRF token mismatch', ['ip' => client_ip(), 'uri' => $_SERVER['REQUEST_URI'] ?? '']);
            return false;
        }

        return true;
    }
}

/**
 * Require a valid CSRF token for the current POST request or die with 419.
 */
if (!function_exists('require_csrf')) {
    function require_csrf(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (!verify_csrf($token)) {
            http_response_code(419);
            die('Your session has expired. Please refresh the page and try again.');
        }
    }
}

/**
 * Brute-force / rate limiting backed by the login_attempts table.
 * $identifier is typically "email:ip" or just the IP for anonymous endpoints.
 */
if (!function_exists('is_rate_limited')) {
    function is_rate_limited(string $identifier): bool
    {
        $stmt = db()->prepare('SELECT * FROM login_attempts WHERE identifier = ? LIMIT 1');
        $stmt->execute([$identifier]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        if ($row['blocked_until'] !== null && strtotime((string) $row['blocked_until']) > time()) {
            return true;
        }

        return false;
    }
}

if (!function_exists('register_failed_attempt')) {
    function register_failed_attempt(string $identifier): void
    {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM login_attempts WHERE identifier = ? LIMIT 1');
        $stmt->execute([$identifier]);
        $row = $stmt->fetch();

        if (!$row) {
            $stmt = $pdo->prepare(
                'INSERT INTO login_attempts (identifier, ip_address, attempts, last_attempt_at, blocked_until)
                 VALUES (?, ?, 1, NOW(), NULL)'
            );
            $stmt->execute([$identifier, client_ip()]);
            return;
        }

        $attempts = (int) $row['attempts'] + 1;
        $blockedUntil = null;

        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $blockedUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_SECONDS);
            $attempts = 0; // reset counter once locked out
        }

        $stmt = $pdo->prepare(
            'UPDATE login_attempts SET attempts = ?, last_attempt_at = NOW(), blocked_until = ?, ip_address = ?
             WHERE identifier = ?'
        );
        $stmt->execute([$attempts, $blockedUntil, client_ip(), $identifier]);
    }
}

if (!function_exists('clear_failed_attempts')) {
    function clear_failed_attempts(string $identifier): void
    {
        $stmt = db()->prepare('DELETE FROM login_attempts WHERE identifier = ?');
        $stmt->execute([$identifier]);
    }
}

/**
 * Validate password strength: min 8 chars, at least one letter and one number.
 */
if (!function_exists('is_strong_password')) {
    function is_strong_password(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[A-Za-z]/', $password) === 1
            && preg_match('/[0-9]/', $password) === 1;
    }
}

/**
 * Validate an email address format.
 */
if (!function_exists('is_valid_email')) {
    function is_valid_email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * Send the standard set of security headers.
 */
if (!function_exists('send_security_headers')) {
    function send_security_headers(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-XSS-Protection: 1; mode=block');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        // Every script/style/font/icon the app uses is self-hosted (no CDN
        // dependency anywhere), so this is a genuine restriction rather than
        // a no-op: it blocks a would-be XSS payload from exfiltrating data
        // to, or loading further script from, any third-party origin.
        // 'unsafe-inline' is still needed for script/style because the
        // templates use inline <script> blocks and style="" attributes
        // throughout; tightening that further would require moving every
        // inline script to an external file with a nonce, which is a larger
        // refactor than this pass covers.
        header(
            "Content-Security-Policy: default-src 'self'; "
            . "script-src 'self' 'unsafe-inline'; "
            . "style-src 'self' 'unsafe-inline'; "
            . "img-src 'self' data:; "
            . "font-src 'self'; "
            . "object-src 'none'; "
            . "base-uri 'self'; "
            . "form-action 'self'; "
            . "frame-ancestors 'self';"
        );
    }
}
