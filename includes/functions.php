<?php
/**
 * General-purpose helper functions used across the entire platform.
 */

declare(strict_types=1);

/**
 * Write a line to the application log file (logs/app-YYYY-MM-DD.log).
 */
if (!function_exists('app_log')) {
    function app_log(string $level, string $message, array $context = []): void
    {
        $dir = defined('BASE_PATH') ? BASE_PATH . '/logs' : __DIR__ . '/../logs';

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $file = $dir . '/app-' . date('Y-m-d') . '.log';
        $line = sprintf(
            '[%s] %s: %s %s%s',
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_SLASHES) : '',
            PHP_EOL
        );

        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Redirect helper (always exits). Flushes the response to the client
 * immediately when running under PHP-FPM, so any work deferred via
 * defer_after_response() (e.g. sending a login-notification email)
 * happens after the browser has already moved on to the next page
 * instead of making the user wait on it.
 */
if (!function_exists('redirect')) {
    function redirect(string $url): never
    {
        header('Location: ' . $url);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        exit;
    }
}

/**
 * Queue a callable to run after the response has been sent to the
 * client (via redirect()'s fastcgi_finish_request(), or otherwise at
 * script end) rather than blocking the current request on it. Used for
 * best-effort side effects like login-notification emails, where a
 * slow SMTP server shouldn't make every login/page load feel sluggish.
 */
if (!function_exists('defer_after_response')) {
    function defer_after_response(callable $fn): void
    {
        register_shutdown_function($fn);
    }
}

/**
 * Escape a string for safe HTML output.
 */
if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Sanitize a plain text input (trim + strip tags).
 */
if (!function_exists('clean')) {
    function clean(mixed $value): string
    {
        return trim(strip_tags((string) $value));
    }
}

/**
 * Format a numeric amount as platform currency, e.g. ₦1,250.00
 */
if (!function_exists('money')) {
    function money(float|int|string $amount, bool $withSymbol = true): string
    {
        $symbol = get_setting('currency_symbol', '₦');
        $formatted = number_format((float) $amount, 2);

        return $withSymbol ? $symbol . $formatted : $formatted;
    }
}

/**
 * NGN-per-USD rate, set manually by the admin (Settings -> Regional).
 * Deliberately not fetched from a live FX API: every wallet balance,
 * deposit and mining plan price is stored in NGN, so a rate that moves
 * mid-transaction would be confusing and hard to reconcile. The admin
 * updates this figure whenever they want the displayed USD conversion
 * to move.
 */
if (!function_exists('usd_rate')) {
    function usd_rate(): float
    {
        $rate = (float) get_setting('usd_exchange_rate', 1500);

        return $rate > 0 ? $rate : 1500.0;
    }
}

if (!function_exists('to_usd')) {
    function to_usd(float $ngnAmount): float
    {
        return $ngnAmount / usd_rate();
    }
}

if (!function_exists('to_ngn')) {
    function to_ngn(float $usdAmount): float
    {
        return $usdAmount * usd_rate();
    }
}

/**
 * Format a NGN amount as its USD equivalent, e.g. $12.34
 */
if (!function_exists('money_usd')) {
    function money_usd(float $ngnAmount): string
    {
        return '$' . number_format(to_usd($ngnAmount), 2);
    }
}

/**
 * Mask a full name for public display (e.g. on the landing page's live
 * activity feed) so real user activity can be shown as social proof
 * without exposing a real person's full identity: "Chidinma Okafor"
 * becomes "Chi***a O."
 */
if (!function_exists('mask_name')) {
    function mask_name(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = $parts[0] ?? '';
        $lastInitial = isset($parts[1]) ? strtoupper($parts[1][0]) . '.' : '';

        if (mb_strlen($first) <= 3) {
            $masked = $first;
        } else {
            $masked = mb_substr($first, 0, 3) . str_repeat('*', max(mb_strlen($first) - 4, 1)) . mb_substr($first, -1);
        }

        return trim($masked . ' ' . $lastInitial);
    }
}

/**
 * Generate a cryptographically secure random token (hex string).
 */
if (!function_exists('generate_token')) {
    function generate_token(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }
}

/**
 * Generate a unique, human-friendly referral code.
 */
if (!function_exists('generate_referral_code')) {
    function generate_referral_code(string $username): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $username));
        $base = substr($base !== '' ? $base : 'USER', 0, 6);

        do {
            $code = $base . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            $stmt = db()->prepare('SELECT id FROM users WHERE referral_code = ? LIMIT 1');
            $stmt->execute([$code]);
        } while ($stmt->fetch());

        return $code;
    }
}

/**
 * Human readable "time ago" string.
 */
if (!function_exists('time_ago')) {
    function time_ago(string $datetime): string
    {
        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return $datetime;
        }

        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'just now';
        }

        $intervals = [
            31536000 => 'year',
            2592000  => 'month',
            604800   => 'week',
            86400    => 'day',
            3600     => 'hour',
            60       => 'minute',
        ];

        foreach ($intervals as $seconds => $label) {
            $count = intdiv($diff, $seconds);
            if ($count >= 1) {
                return $count . ' ' . $label . ($count > 1 ? 's' : '') . ' ago';
            }
        }

        return 'just now';
    }
}

/**
 * Flash message helpers (session based, one-time read).
 */
if (!function_exists('flash')) {
    function flash(string $key, ?string $message = null, string $type = 'info'): ?array
    {
        if ($message !== null) {
            $_SESSION['_flash'][$key] = ['message' => $message, 'type' => $type];
            return null;
        }

        if (isset($_SESSION['_flash'][$key])) {
            $data = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $data;
        }

        return null;
    }
}

/**
 * Retrieve a site setting from the cached settings array (loaded from DB),
 * falling back to .env / a supplied default.
 */
if (!function_exists('get_setting')) {
    function get_setting(string $key, mixed $default = null): mixed
    {
        global $SITE_SETTINGS;

        if (isset($SITE_SETTINGS) && array_key_exists($key, $SITE_SETTINGS)) {
            return $SITE_SETTINGS[$key];
        }

        return $default;
    }
}

/**
 * Persist (insert or update) a site setting both in DB and in-memory cache.
 */
if (!function_exists('set_setting')) {
    function set_setting(string $key, string $value): void
    {
        global $SITE_SETTINGS;

        $stmt = db()->prepare(
            'INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$key, $value]);

        $SITE_SETTINGS[$key] = $value;
    }
}

/**
 * Get the currently authenticated user's full row, or null.
 */
if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        static $user = null;
        static $loaded = false;

        if ($loaded) {
            return $user;
        }

        $loaded = true;

        if (empty($_SESSION['user_id'])) {
            return null;
        }

        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;

        return $user;
    }
}

/**
 * Get the currently authenticated admin's full row, or null.
 */
if (!function_exists('current_admin')) {
    function current_admin(): ?array
    {
        static $admin = null;
        static $loaded = false;

        if ($loaded) {
            return $admin;
        }

        $loaded = true;

        if (empty($_SESSION['admin_id'])) {
            return null;
        }

        $stmt = db()->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch() ?: null;

        return $admin;
    }
}

/**
 * Get the real client IP address, honouring common proxy headers.
 */
if (!function_exists('client_ip')) {
    function client_ip(): string
    {
        // Proxy headers (X-Forwarded-For, CF-Connecting-IP, ...) are sent by
        // the CLIENT and are trivially spoofable — trusting them by default
        // would let anyone bypass every IP-based rate limit (login lockout,
        // registration/contact throttling) just by sending a fake header.
        // Only honour them if this deployment is actually behind a proxy
        // that strips/overwrites client-supplied values (e.g. Cloudflare),
        // which the site owner opts into explicitly via TRUST_PROXY_HEADERS.
        if (env('TRUST_PROXY_HEADERS', false)) {
            foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP'] as $key) {
                if (!empty($_SERVER[$key])) {
                    $ip = trim(explode(',', $_SERVER[$key])[0]);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}

/**
 * Record an activity log entry.
 */
if (!function_exists('log_activity')) {
    function log_activity(?int $userId, ?int $adminId, string $action, string $description = ''): void
    {
        try {
            $stmt = db()->prepare(
                'INSERT INTO activity_logs (user_id, admin_id, action, description, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $userId,
                $adminId,
                $action,
                $description,
                client_ip(),
                substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
        } catch (Throwable $e) {
            app_log('error', 'Failed to write activity log: ' . $e->getMessage());
        }
    }
}

/**
 * Create a notification for a user (or a broadcast when $userId is null).
 */
if (!function_exists('notify_user')) {
    function notify_user(?int $userId, string $title, string $message, string $type = NOTIFY_TYPE_SYSTEM): void
    {
        $stmt = db()->prepare(
            'INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
             VALUES (?, ?, ?, ?, 0, NOW())'
        );
        $stmt->execute([$userId, $title, $message, $type]);
    }
}

/**
 * Validate an uploaded file against allowed mime types / size, returns error string or null if valid.
 */
if (!function_exists('validate_upload')) {
    function validate_upload(array $file, array $allowedMimes, int $maxBytes): ?string
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            return 'Invalid file upload.';
        }

        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return 'No file was uploaded.';
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'File upload failed. Please try again.';
        }

        if ($file['size'] > $maxBytes) {
            return 'File is too large. Maximum size is ' . round($maxBytes / 1048576, 1) . 'MB.';
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowedMimes, true)) {
            return 'Invalid file type.';
        }

        return null;
    }
}

/**
 * Move an uploaded file into a target directory with a random, safe filename.
 * Returns the relative stored path.
 */
if (!function_exists('store_upload')) {
    function store_upload(array $file, string $subDir): string
    {
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $ext = $extMap[$mime] ?? pathinfo($file['name'], PATHINFO_EXTENSION);
        $ext = preg_replace('/[^a-z0-9]/i', '', $ext) ?: 'bin';

        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
        $targetDir = rtrim(BASE_PATH, '/') . '/uploads/' . trim($subDir, '/');

        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new RuntimeException('Failed to store uploaded file.');
        }

        return trim($subDir, '/') . '/' . $filename;
    }
}

/**
 * Paginate a value between min/max (used for limits, page sizes, etc).
 */
if (!function_exists('clamp')) {
    function clamp(int|float $value, int|float $min, int|float $max): int|float
    {
        return max($min, min($max, $value));
    }
}

/**
 * Render the brand mark (custom uploaded logo if the admin has set one,
 * otherwise the site name's initial letter), shared by every nav/sidebar.
 */
if (!function_exists('brand_mark_html')) {
    function brand_mark_html(int $size = 36): string
    {
        $logo = (string) get_setting('site_logo', '');

        if ($logo !== '') {
            $src = e(rtrim(APP_URL, '/')) . '/uploads/' . e($logo);
            return '<img src="' . $src . '" alt="Logo" class="brand-mark" style="width:' . $size . 'px;height:' . $size . 'px;object-fit:contain;padding:4px;">';
        }

        $initial = strtoupper(substr((string) get_setting('site_name', 'SURECASH MINING'), 0, 1)) ?: 'S';

        return '<span class="brand-mark" style="width:' . $size . 'px;height:' . $size . 'px;">' . e($initial) . '</span>';
    }
}

/**
 * Render the <link rel="icon"> tag: the admin-uploaded logo if one has
 * been set (so a re-branded site gets its own favicon for free),
 * otherwise the bundled default favicon.svg.
 */
if (!function_exists('favicon_link_html')) {
    function favicon_link_html(): string
    {
        $logo = (string) get_setting('site_logo', '');
        $assetBase = e(rtrim(APP_URL, '/')) . '/assets';

        if ($logo === '') {
            return '<link rel="icon" href="' . $assetBase . '/images/logo/favicon.svg" type="image/svg+xml">';
        }

        $mimeByExt = [
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
        ];
        $ext = strtolower(pathinfo($logo, PATHINFO_EXTENSION));
        $type = $mimeByExt[$ext] ?? 'image/png';
        $src = e(rtrim(APP_URL, '/')) . '/uploads/' . e($logo);

        return '<link rel="icon" href="' . $src . '" type="' . $type . '">';
    }
}
