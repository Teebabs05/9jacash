<?php
/**
 * Verifies a biometric login assertion and, on success, establishes a
 * full user or admin session exactly like a normal password login
 * would. Public endpoint (no login required - this IS how you log in).
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true) ?: [];

if (!verify_csrf($body['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Your session has expired. Please refresh the page.']);
    exit;
}

$context = $body['context'] ?? '';

if (!in_array($context, ['user', 'admin'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid login context.']);
    exit;
}

$credential = $body['credential'] ?? null;

if (!is_array($credential)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid login response.']);
    exit;
}

$result = webauthn_verify_login($context, $credential);

if (!$result['success']) {
    echo json_encode($result);
    exit;
}

$loginResult = $context === 'user'
    ? Auth::completeBiometricLogin($result['owner_id'])
    : AdminAuth::completeBiometricLogin($result['owner_id']);

if (!$loginResult['success']) {
    echo json_encode($loginResult);
    exit;
}

$redirectUrl = $context === 'user'
    ? rtrim(APP_URL, '/') . '/user/dashboard.php'
    : rtrim(APP_URL, '/') . '/admin/index.php';

echo json_encode(['success' => true, 'redirect' => $redirectUrl]);

// Flush the response now (if running under PHP-FPM) so the browser can
// navigate immediately - the login-notification email queued inside
// completeBiometricLogin() above sends afterward, in the background.
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}
