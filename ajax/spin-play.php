<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in again.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!verify_csrf($token)) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Your session has expired. Please refresh the page.']);
    exit;
}

$user = current_user();
$result = spin_play((int) $user['id']);

if ($result['success']) {
    $extraPrice = spin_extra_price();
    $balance = wallet_total_balance((int) $user['id']);

    $result['daily_limit_reached'] = !spin_can_play((int) $user['id']);
    $result['extra_price'] = $extraPrice;
    $result['extra_price_formatted'] = money($extraPrice);
    $result['can_afford_extra'] = $balance >= $extraPrice;
}

echo json_encode($result);
