<?php
/**
 * Lightweight WAF / scanner firewall.
 *
 * Runs on every request (see config/config.php) before auth, session
 * business logic, or any module code executes. Blocks the automated
 * vulnerability scanners and exploit-probe requests that constantly hit
 * public web servers looking for WordPress installs, exposed .git/.env
 * files, admin panels (phpMyAdmin/Adminer), webshells and classic
 * SQLi/XSS/RCE payloads. Offending IPs are auto-banned so the same
 * scanner is rejected instantly on every later request without needing
 * to re-run any pattern matching.
 */

declare(strict_types=1);

// How long a confirmed scanner/exploit-probe IP stays banned. Each new
// hit from an already-banned IP refreshes this window, so an actively
// probing scanner effectively stays blocked for as long as it keeps
// trying, while a one-off false positive ages out automatically.
const FIREWALL_BAN_SECONDS = 86400; // 24 hours

// Case-insensitive substrings matched against the User-Agent header.
// Kept to well-known scanner/exploit-tool names only - deliberately
// excludes generic HTTP client UAs (curl, python-requests, Go-http-
// client, ...) since legitimate server-to-server calls (e.g. payment
// gateway webhooks) use those too.
const FIREWALL_SCANNER_USER_AGENTS = [
    'sqlmap', 'nikto', 'nessus', 'openvas', 'acunetix', 'netsparker',
    'arachni', 'w3af', 'nmap', 'masscan', 'zgrab', 'zmeu', 'wpscan',
    'dirbuster', 'gobuster', 'feroxbuster', 'dirb', 'ffuf', 'havij',
    'metasploit', 'nuclei', 'jaeles', 'nimbostratus', 'censysinspect',
    'sqlninja', 'libssh',
];

// Path patterns for requests that have no legitimate reason to hit this
// codebase - WordPress/CMS probes, exposed VCS/config directories,
// database admin tools, webshell filenames, framework debug endpoints.
// None of these overlap with real application routes (admin/, api/,
// ajax/, user/, wallet/, payments/, mining/, tasks/, ads/, spin/,
// checkin/, webauthn/, install/, uploads/, assets/).
const FIREWALL_EXPLOIT_PATH_PATTERNS = [
    '#^/wp-(login\.php|admin|content|includes|json)#i',
    '#^/xmlrpc\.php#i',
    '#(^|/)\.git(/|$)#i',
    '#(^|/)\.svn(/|$)#i',
    '#(^|/)\.hg(/|$)#i',
    '#(^|/)\.env$#i',
    '#^/(phpmyadmin|pma|myadmin|php-my-admin)(/|$)#i',
    '#^/adminer(\.php)?(/|$)#i',
    '#^/\.aws(/|$)#i',
    '#^/\.ssh(/|$)#i',
    '#(^|/)id_rsa$#i',
    '#^/actuator(/|$)#i',
    '#^/telescope(/|$)#i',
    '#^/_profiler(/|$)#i',
    '#^/solr(/|$)#i',
    '#^/cgi-bin(/|$)#i',
    '#eval-stdin\.php#i',
    '#^/(shell|c99|r57|wso|b374k|webshell)\.php#i',
    '#^/server-status#i',
    '#^/server-info#i',
    '#docker-compose\.ya?ml$#i',
    '#/etc/passwd#i',
    '#boot\.ini#i',
];

// Payload signatures (SQLi / XSS / path traversal / remote code
// execution) checked against the decoded request URI + query string.
// POST bodies are intentionally not scanned here: every database query
// in this codebase already uses PDO prepared statements, so free-text
// form fields (support messages, KYC notes, etc.) are not a SQLi vector
// and scanning them would produce false positives on ordinary user
// input.
const FIREWALL_WAF_PATTERNS = [
    '/\bunion\b.{0,40}\bselect\b/i',
    '/\bselect\b.{0,60}\bfrom\b.{0,40}\binformation_schema\b/i',
    '/\bor\b\s+[\'"]?1[\'"]?\s*=\s*[\'"]?1/i',
    '/\bsleep\(\s*\d+\s*\)/i',
    '/\bbenchmark\(\s*\d+/i',
    '/\bxp_cmdshell\b/i',
    '/<script[\s>]/i',
    '/on(error|load|mouseover|focus)\s*=/i',
    '/javascript:\s*[a-z]/i',
    '/document\.cookie/i',
    '/\.\.\/\.\.\/\.\./i',
    '/\.\.%2f\.\.%2f/i',
    '/\/etc\/passwd/i',
    '/\bbase64_decode\(/i',
    '/\bphpinfo\(\)/i',
    '/\b(system|passthru|shell_exec|proc_open)\(/i',
];

/**
 * Whether $ip currently has an active ban.
 */
if (!function_exists('is_ip_blocked')) {
    function is_ip_blocked(string $ip): bool
    {
        $stmt = db()->prepare('SELECT blocked_until FROM blocked_ips WHERE ip_address = ? LIMIT 1');
        $stmt->execute([$ip]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        return $row['blocked_until'] === null || strtotime((string) $row['blocked_until']) > time();
    }
}

/**
 * Ban (or extend the ban on) an IP address.
 */
if (!function_exists('ban_ip')) {
    function ban_ip(string $ip, string $reason): void
    {
        $blockedUntil = date('Y-m-d H:i:s', time() + FIREWALL_BAN_SECONDS);

        $stmt = db()->prepare(
            'INSERT INTO blocked_ips (ip_address, reason, strikes, blocked_until)
             VALUES (?, ?, 1, ?)
             ON DUPLICATE KEY UPDATE reason = VALUES(reason), strikes = strikes + 1, blocked_until = VALUES(blocked_until)'
        );
        $stmt->execute([$ip, $reason, $blockedUntil]);
    }
}

/**
 * Lift a ban early (used by the admin security dashboard).
 */
if (!function_exists('unban_ip')) {
    function unban_ip(string $ip): void
    {
        db()->prepare('DELETE FROM blocked_ips WHERE ip_address = ?')->execute([$ip]);
    }
}

/**
 * Record a firewall trip for later review in the admin security
 * dashboard, and mirror it into the app log.
 */
if (!function_exists('log_security_event')) {
    function log_security_event(string $ip, string $type, string $detail = ''): void
    {
        try {
            $stmt = db()->prepare(
                'INSERT INTO security_events (ip_address, event_type, request_uri, user_agent, detail)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $ip,
                $type,
                substr((string) ($_SERVER['REQUEST_URI'] ?? ''), 0, 2048),
                substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512),
                $detail,
            ]);
        } catch (Throwable $e) {
            // Never let a logging failure take down the firewall itself.
        }

        app_log('warning', 'Firewall: ' . $type, [
            'ip' => $ip,
            'detail' => $detail,
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
        ]);
    }
}

/**
 * Reject the current request with a bare 403 and stop execution.
 */
if (!function_exists('deny_request')) {
    function deny_request(): void
    {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        die('403 Forbidden');
    }
}

/**
 * Entry point called once per request from config/config.php, before
 * any auth/session/business-logic module runs.
 */
if (!function_exists('block_malicious_requests')) {
    function block_malicious_requests(): void
    {
        $ip = client_ip();
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

        if (is_ip_blocked($ip)) {
            log_security_event($ip, 'banned_ip_retry');
            deny_request();
        }

        foreach (FIREWALL_SCANNER_USER_AGENTS as $signature) {
            if ($ua !== '' && stripos($ua, $signature) !== false) {
                ban_ip($ip, 'Scanner user-agent: ' . $signature);
                log_security_event($ip, 'scanner_ua', $signature);
                deny_request();
            }
        }

        $path = (string) parse_url($uri, PHP_URL_PATH);
        foreach (FIREWALL_EXPLOIT_PATH_PATTERNS as $pattern) {
            if (preg_match($pattern, $path) === 1) {
                ban_ip($ip, 'Exploit-probe path: ' . $path);
                log_security_event($ip, 'exploit_path', $path);
                deny_request();
            }
        }

        $decodedUri = rawurldecode($uri);
        foreach (FIREWALL_WAF_PATTERNS as $pattern) {
            if (preg_match($pattern, $decodedUri) === 1) {
                ban_ip($ip, 'Malicious request pattern');
                log_security_event($ip, 'waf_pattern', $pattern);
                deny_request();
            }
        }
    }
}
