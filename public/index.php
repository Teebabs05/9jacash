<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require APP_PATH . '/helpers.php';

use App\Core\Router;

$router = new Router();
require APP_PATH . '/routes.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
