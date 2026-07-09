<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (!AdminAuth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in again.']);
    exit;
}

$q = trim((string) ($_GET['q'] ?? ''));

if (strlen($q) < 2) {
    echo json_encode(['success' => true, 'users' => []]);
    exit;
}

$like = '%' . $q . '%';
$stmt = db()->prepare(
    "SELECT id, username, email, full_name FROM users
     WHERE username LIKE ? OR email LIKE ? OR full_name LIKE ?
     ORDER BY full_name ASC LIMIT 20"
);
$stmt->execute([$like, $like, $like]);

echo json_encode(['success' => true, 'users' => $stmt->fetchAll()]);
