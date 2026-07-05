<?php
/**
 * Lightweight bootstrap used only by the installation wizard.
 * Does NOT require config.php (DB credentials don't exist yet) or
 * config/database.php — the wizard manages its own PDO connections.
 */

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

define('INSTALLER_CONTEXT', true);

require_once BASE_PATH . '/config/env.php';
load_env_file(BASE_PATH . '/.env');

require_once BASE_PATH . '/config/constants.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/session.php';
require_once BASE_PATH . '/includes/security.php';

if (!defined('APP_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $selfDir = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')));
    define('APP_URL', $scheme . $host . rtrim($selfDir, '/'));
}

require_once __DIR__ . '/install-functions.php';
