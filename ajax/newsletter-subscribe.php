<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!verify_csrf($token)) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Your session has expired. Please refresh the page and try again.']);
    exit;
}

$email = strtolower(clean($_POST['email'] ?? ''));

if (!is_valid_email($email)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

try {
    $stmt = db()->prepare('INSERT INTO newsletter_subscribers (email, created_at) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE email = email');
    $stmt->execute([$email]);

    echo json_encode(['success' => true, 'message' => 'Thanks for subscribing! You will receive our latest updates.']);
} catch (Throwable $e) {
    app_log('error', 'Newsletter subscribe failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again shortly.']);
}
