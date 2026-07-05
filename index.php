<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

// The full marketing landing page ships in the next build phase.
// For now, route visitors straight into the app.
if (Auth::isLoggedIn()) {
    redirect(rtrim(APP_URL, '/') . '/user/dashboard.php');
}

redirect(rtrim(APP_URL, '/') . '/user/login.php');
