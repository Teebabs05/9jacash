<?php
/**
 * SURECASH MINING master application bootstrap.
 *
 * Every public entry point (index.php, user/*, admin/*, api/*, ajax/*, cron/*)
 * must require this file before doing anything else. It wires together
 * environment loading, the database connection, security helpers,
 * authentication services and the site settings cache.
 */

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/env.php';
load_env_file(BASE_PATH . '/.env');

require_once BASE_PATH . '/includes/functions.php';

// ---------------------------------------------------------------
// Guard: force a fresh deployment through the install wizard until
// it has completed successfully.
// ---------------------------------------------------------------
$installLock = BASE_PATH . '/install/installed.lock';

if (!defined('INSTALLER_CONTEXT') && !is_file($installLock)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $installUrl = preg_replace('#/(admin|user|api|ajax|wallet|payments|mining|tasks|ads|spin|checkin)$#', '', $scriptDir);
    redirect(rtrim($installUrl, '/') . '/install/index.php');
}

require_once BASE_PATH . '/config.php';
require_once BASE_PATH . '/config/constants.php';

// PHP's timezone must be fixed *before* the database connects — the DB
// layer syncs MySQL's session `time_zone` to match this so that a
// MySQL-generated NOW()/CURDATE() value and PHP's time()/date() always
// agree on the same wall clock. (Site-settings can override this later
// once the DB is up; both start from the same .env default so they
// normally match anyway.)
date_default_timezone_set(env('APP_TIMEZONE', 'Africa/Lagos'));

require_once BASE_PATH . '/config/database.php';

if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

require_once BASE_PATH . '/includes/session.php';
require_once BASE_PATH . '/includes/security.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/admin-auth.php';
require_once BASE_PATH . '/includes/mailer.php';
require_once BASE_PATH . '/includes/wallet.php';
require_once BASE_PATH . '/includes/mining.php';
require_once BASE_PATH . '/includes/ads.php';
require_once BASE_PATH . '/includes/spin.php';
require_once BASE_PATH . '/includes/checkin.php';
require_once BASE_PATH . '/includes/payvessel.php';
require_once BASE_PATH . '/includes/deposits.php';
require_once BASE_PATH . '/includes/withdrawals.php';
require_once BASE_PATH . '/includes/referrals.php';
require_once BASE_PATH . '/includes/support.php';
require_once BASE_PATH . '/includes/webauthn.php';

// ---------------------------------------------------------------
// Load site settings from the database into a global cache.
// ---------------------------------------------------------------
$GLOBALS['SITE_SETTINGS'] = [];

try {
    $rows = db()->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
    foreach ($rows as $row) {
        $GLOBALS['SITE_SETTINGS'][$row['setting_key']] = $row['setting_value'];
    }
} catch (Throwable $e) {
    app_log('warning', 'Could not preload site_settings: ' . $e->getMessage());
}

// ---------------------------------------------------------------
// Environment-driven runtime configuration.
// ---------------------------------------------------------------
$configuredTimezone = (string) get_setting('timezone', env('APP_TIMEZONE', 'Africa/Lagos'));
if ($configuredTimezone !== date_default_timezone_get()) {
    date_default_timezone_set($configuredTimezone);
    sync_db_timezone();
}

$debug = defined('APP_DEBUG') && APP_DEBUG === true;
error_reporting($debug ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/logs/php-error.log');

if (!defined('INSTALLER_CONTEXT')) {
    send_security_headers();

    if ((bool) get_setting('maintenance_mode', false) && !AdminAuth::isLoggedIn()) {
        if (!str_contains((string) ($_SERVER['REQUEST_URI'] ?? ''), '/admin/')) {
            http_response_code(503);
            $message = (string) get_setting('maintenance_message', 'We are currently performing scheduled maintenance. Please check back shortly.');
            die('<!DOCTYPE html><html><head><title>Maintenance</title><meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="font-family:Arial,sans-serif;text-align:center;padding:80px 20px;background:#0B2545;color:#fff;"><h1>Under Maintenance</h1><p>' . e($message) . '</p></body></html>');
        }
    }
}
