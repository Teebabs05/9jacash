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

$identifier = 'contact:' . client_ip();
if (is_rate_limited($identifier)) {
    echo json_encode(['success' => false, 'message' => 'Too many messages sent. Please try again later.']);
    exit;
}

$name = clean($_POST['name'] ?? '');
$email = strtolower(clean($_POST['email'] ?? ''));
$subject = clean($_POST['subject'] ?? '');
$message = clean($_POST['message'] ?? '');

$errors = [];

if (strlen($name) < 2) {
    $errors[] = 'Please enter your name.';
}

if (!is_valid_email($email)) {
    $errors[] = 'Please enter a valid email address.';
}

if (strlen($message) < 10) {
    $errors[] = 'Please enter a message of at least 10 characters.';
}

if ($errors) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

try {
    $stmt = db()->prepare(
        'INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())'
    );
    $stmt->execute([$name, $email, $subject, $message]);
    register_failed_attempt($identifier); // reuse as a throttle counter

    Mailer::send(
        (string) get_setting('contact_email', 'support@9jacash.com'),
        'Support Team',
        'New Contact Message: ' . ($subject ?: 'General Inquiry'),
        "<p><strong>From:</strong> " . e($name) . " (" . e($email) . ")</p><p>" . nl2br(e($message)) . "</p>"
    );

    echo json_encode(['success' => true, 'message' => 'Thanks for reaching out! Our team will get back to you shortly.']);
} catch (Throwable $e) {
    app_log('error', 'Contact form submit failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again shortly.']);
}
