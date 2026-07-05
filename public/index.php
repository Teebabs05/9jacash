<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require APP_PATH . '/helpers.php';

use App\Core\Router;

$path = trim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$allowedDuringMaintenance = $path === 'login' || $path === 'logout' || str_starts_with($path, 'admin');

if (setting('maintenance_mode') === '1' && !is_admin() && !$allowedDuringMaintenance) {
    http_response_code(503);
    view('pages/maintenance', ['title' => 'Under Maintenance'], null);
    exit;
}

$router = new Router();
require APP_PATH . '/routes.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
