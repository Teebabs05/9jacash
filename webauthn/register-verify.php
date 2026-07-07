<?php
/**
 * Verifies a registration ceremony response and stores the credential
 * for the currently logged-in user or admin.
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

$body = json_decode(file_get_contents('php://input'), true) ?: [];

if (!verify_csrf($body['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Your session has expired. Please refresh the page.']);
    exit;
}

$credential = $body['credential'] ?? null;

if (!is_array($credential)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid registration response.']);
    exit;
}

$deviceLabel = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
$deviceLabel = $deviceLabel !== '' ? substr($deviceLabel, 0, 100) : '';

$result = webauthn_verify_registration($ownerType, (int) $owner['id'], $credential, $deviceLabel);

if ($result['success']) {
    log_activity($ownerType === 'user' ? (int) $owner['id'] : null, $ownerType === 'admin' ? (int) $owner['id'] : null, 'webauthn_registered', 'Enabled biometric login');
}

echo json_encode($result);
