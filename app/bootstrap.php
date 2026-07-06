<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);
define('STORAGE_PATH', BASE_PATH . '/storage');

// Simple PSR-4-ish autoloader: App\Foo\Bar => app/Foo/Bar.php
spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $relative = substr($class, strlen('App\\'));
    $path = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$config = require APP_PATH . '/config/config.php';

date_default_timezone_set($config['app']['timezone']);

error_reporting(E_ALL);
ini_set('display_errors', $config['app']['debug'] ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');

App\Core\App::setConfig($config);

if (PHP_SAPI !== 'cli') {
    App\Core\Session::start($config['session']['lifetime']);

    if (!is_file(BASE_PATH . '/.env') && !str_contains($_SERVER['REQUEST_URI'] ?? '', '/install')) {
        header('Location: /install/');
        exit;
    }
}
