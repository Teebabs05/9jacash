<?php
/**
 * Secure session bootstrap. Must be included before any output.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $secure = (defined('APP_ENV') && APP_ENV === 'production')
        || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name('SURECASHSESSID');
    session_start();
}

// Regenerate the session ID periodically to mitigate fixation attacks.
if (empty($_SESSION['_last_regen'])) {
    $_SESSION['_last_regen'] = time();
} elseif (time() - $_SESSION['_last_regen'] > 900) {
    session_regenerate_id(true);
    $_SESSION['_last_regen'] = time();
}
