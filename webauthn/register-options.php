<?php
/**
 * Returns PublicKeyCredentialCreationOptions (JSON) for the currently
 * logged-in user or admin to register a new biometric credential.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (Auth::isLoggedIn()) {
    $owner = current_user();
    $ownerType = 'user';
} elseif (AdminAuth::isLoggedIn()) {
    $owner = current_admin();
    $ownerType = 'admin';
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'You must be logged in.']);
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Your session has expired. Please refresh the page.']);
    exit;
}

$options = webauthn_registration_options(
    $ownerType,
    (int) $owner['id'],
    $owner['username'] ?? $owner['email'],
    $owner['full_name'] ?? $owner['username']
);

echo json_encode(['success' => true, 'options' => $options]);
