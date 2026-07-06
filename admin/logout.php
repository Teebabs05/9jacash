<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::logout();
redirect(rtrim(APP_URL, '/') . '/admin/login.php');
