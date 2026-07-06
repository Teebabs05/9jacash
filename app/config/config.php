<?php

declare(strict_types=1);

use App\Core\Env;

Env::load(dirname(__DIR__, 2) . '/.env');

return [
    'app' => [
        'name' => Env::get('APP_NAME', '9JACASH'),
        'url' => rtrim((string) Env::get('APP_URL', 'http://localhost'), '/'),
        'env' => Env::get('APP_ENV', 'production'),
        'debug' => filter_var(Env::get('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
        'key' => Env::get('APP_KEY', ''),
        'timezone' => 'Africa/Lagos',
    ],
    'db' => [
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => Env::get('DB_PORT', '3306'),
        'name' => Env::get('DB_NAME', '9jacash'),
        'user' => Env::get('DB_USER', 'root'),
        'pass' => Env::get('DB_PASS', ''),
        'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
    ],
    'mail' => [
        'host' => Env::get('MAIL_HOST', ''),
        'port' => (int) Env::get('MAIL_PORT', 587),
        'encryption' => Env::get('MAIL_ENCRYPTION', 'tls'),
        'username' => Env::get('MAIL_USERNAME', ''),
        'password' => Env::get('MAIL_PASSWORD', ''),
        'from_address' => Env::get('MAIL_FROM_ADDRESS', 'no-reply@9jacash.com'),
        'from_name' => Env::get('MAIL_FROM_NAME', '9JACASH'),
    ],
    'recaptcha' => [
        'site_key' => Env::get('RECAPTCHA_SITE_KEY', ''),
        'secret_key' => Env::get('RECAPTCHA_SECRET_KEY', ''),
    ],
    'payvessel' => [
        'public_key' => Env::get('PAYVESSEL_PUBLIC_KEY', ''),
        'secret_key' => Env::get('PAYVESSEL_SECRET_KEY', ''),
        'base_url' => rtrim((string) Env::get('PAYVESSEL_BASE_URL', 'https://api.payvessel.com'), '/'),
        'webhook_secret' => Env::get('PAYVESSEL_WEBHOOK_SECRET', ''),
    ],
    'session' => [
        'lifetime' => (int) Env::get('SESSION_LIFETIME', 120),
    ],
    'cron' => [
        'secret' => Env::get('CRON_SECRET', ''),
    ],
];
