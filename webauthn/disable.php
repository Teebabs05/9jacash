<?php
/**
 * Removes all biometric credentials for the currently logged-in user
 * or admin ("Biometric Login" toggle turned off).
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (Auth::isLoggedIn()) {
    $owner = current_user();
    $ownerType = 'user';
    $redirectUrl = rtrim(APP_URL, '/') . '/user/profile.php';
} elseif (AdminAuth::isLoggedIn()) {
    $owner = current_admin();
    $ownerType = 'admin';
    $redirectUrl = rtrim(APP_URL, '/') . '/admin/profile.php';
} else {
    http_response_code(401);
    exit('You must be logged in.');
}

require_csrf();

db()->prepare('DELETE FROM webauthn_credentials WHERE owner_type = ? AND owner_id = ?')->execute([$ownerType, (int) $owner['id']]);
log_activity($ownerType === 'user' ? (int) $owner['id'] : null, $ownerType === 'admin' ? (int) $owner['id'] : null, 'webauthn_disabled', 'Disabled biometric login');

flash($ownerType === 'user' ? 'profile' : 'admin_profile', 'Biometric login disabled.', 'success');
redirect($redirectUrl);
