<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::logout();
redirect(rtrim(APP_URL, '/') . '/user/login.php');
