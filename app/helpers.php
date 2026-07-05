<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Security;
use App\Core\Session;
use App\Models\Setting;

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return App::config($key, $default);
    }
}

if (!function_exists('db')) {
    function db(): \App\Core\Database
    {
        return App::db();
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return Security::e($value);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Security::csrfField();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Security::csrfToken();
    }
}

if (!function_exists('old')) {
    function old(string $key, string $default = ''): string
    {
        $val = Session::get('_old.' . $key, $default);
        return e($val);
    }
}

if (!function_exists('flash')) {
    function flash(string $key): ?string
    {
        return Session::flash($key);
    }
}

if (!function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        static $cache = null;
        if ($cache === null) {
            $cache = Setting::allCached();
        }
        return $cache[$key] ?? $default;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return base_url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $base = rtrim((string) config('app.url'), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . base_url($path));
        exit;
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        return Session::get('user');
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return Session::has('user');
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        $user = current_user();
        return $user !== null && ($user['role'] ?? '') === 'admin';
    }
}

if (!function_exists('view')) {
    function view(string $name, array $data = [], ?string $layout = 'main'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = APP_PATH . "/views/{$name}.php";
        if (!is_file($viewFile)) {
            http_response_code(500);
            echo "View not found: {$name}";
            return;
        }

        if ($layout === null) {
            require $viewFile;
            return;
        }

        $content = function () use ($viewFile, $data): void {
            extract($data, EXTR_SKIP);
            require $viewFile;
        };

        require APP_PATH . "/views/layouts/{$layout}.php";
    }
}

if (!function_exists('money')) {
    function money(float|int|string $amount, string $currency = '₦'): string
    {
        return $currency . number_format((float) $amount, 2);
    }
}

if (!function_exists('json_response')) {
    function json_response(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('require_login')) {
    function require_login(): void
    {
        if (!is_logged_in()) {
            Session::flash('error', 'Please login to continue.');
            redirect('login');
        }

        $user = current_user();
        $path = current_path();
        if (!empty($user['force_password_change']) && $path !== 'profile/password' && $path !== 'logout') {
            Session::flash('info', 'You must change your password before continuing.');
            redirect('profile/password');
        }
    }
}

if (!function_exists('require_admin')) {
    function require_admin(): void
    {
        require_login();
        if (!is_admin()) {
            http_response_code(403);
            view('pages/403', ['title' => 'Forbidden']);
            exit;
        }
    }
}

if (!function_exists('time_ago')) {
    function time_ago(string $datetime): string
    {
        $diff = time() - strtotime($datetime);
        if ($diff < 60) {
            return 'just now';
        }
        $units = [
            31536000 => 'year', 2592000 => 'month', 86400 => 'day',
            3600 => 'hour', 60 => 'minute',
        ];
        foreach ($units as $seconds => $label) {
            $value = intdiv($diff, $seconds);
            if ($value >= 1) {
                return $value . ' ' . $label . ($value > 1 ? 's' : '') . ' ago';
            }
        }
        return 'just now';
    }
}

if (!function_exists('sanitize')) {
    function sanitize(mixed $value): string
    {
        return Security::sanitizeString($value);
    }
}

if (!function_exists('current_path')) {
    function current_path(): string
    {
        return trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    }
}

if (!function_exists('is_active_path')) {
    function is_active_path(string $path): string
    {
        $current = current_path();
        $path = trim($path, '/');
        if ($path === '') {
            return $current === '' ? 'active' : '';
        }
        return ($current === $path || str_starts_with($current, $path . '/')) ? 'active' : '';
    }
}

if (!function_exists('user_avatar_url')) {
    function user_avatar_url(array $user): string
    {
        if (!empty($user['avatar'])) {
            return base_url('files/avatars/' . basename($user['avatar']));
        }
        return asset('img/default-avatar.svg');
    }
}

if (!function_exists('push_script')) {
    /** Queue an inline <script> block to render after all CDN libraries load. */
    function push_script(string $js): void
    {
        $GLOBALS['__pushed_scripts'][] = $js;
    }
}

if (!function_exists('rendered_scripts')) {
    function rendered_scripts(): string
    {
        return implode("\n", $GLOBALS['__pushed_scripts'] ?? []);
    }
}

if (!function_exists('generate_reference')) {
    function generate_reference(string $prefix = 'TXN'): string
    {
        return strtoupper($prefix) . '-' . date('ymd') . strtoupper(bin2hex(random_bytes(5)));
    }
}
