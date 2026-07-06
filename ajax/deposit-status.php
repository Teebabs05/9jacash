<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'unknown']);
    exit;
}

$user = current_user();
$depositId = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT status FROM deposits WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$depositId, $user['id']]);
$deposit = $stmt->fetch();

echo json_encode(['status' => $deposit['status'] ?? 'unknown']);
