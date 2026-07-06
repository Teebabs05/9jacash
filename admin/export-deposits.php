<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$statusFilter = $_GET['status'] ?? 'all';
if (!in_array($statusFilter, ['pending', 'approved', 'rejected', 'all'], true)) {
    $statusFilter = 'all';
}

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : '';
$to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ? $to : '';

$where = [];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = 'd.status = ?';
    $params[] = $statusFilter;
}
if ($from !== '') {
    $where[] = 'd.created_at >= ?';
    $params[] = $from . ' 00:00:00';
}
if ($to !== '') {
    $where[] = 'd.created_at <= ?';
    $params[] = $to . ' 23:59:59';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = db()->prepare(
    "SELECT d.*, u.username, u.full_name, u.email
     FROM deposits d
     INNER JOIN users u ON u.id = d.user_id
     {$whereSql}
     ORDER BY d.created_at DESC"
);
$stmt->execute($params);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="deposits-' . date('Y-m-d-His') . '.csv"');
header('X-Content-Type-Options: nosniff');

$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'Username', 'Full Name', 'Email', 'Method', 'Amount', 'Charge', 'Reference', 'Status', 'Admin Note', 'Created At'], ',', '"', '\\');

while ($d = $stmt->fetch()) {
    fputcsv($out, [
        $d['id'],
        $d['username'],
        $d['full_name'],
        $d['email'],
        $d['method'],
        sprintf('%.2f', (float) $d['amount']),
        sprintf('%.2f', (float) $d['charge']),
        $d['reference'],
        $d['status'],
        $d['admin_note'],
        $d['created_at'],
    ], ',', '"', '\\');
}

fclose($out);

log_activity(null, (int) current_admin()['id'], 'admin_export_deposits', "Exported deposits CSV (status={$statusFilter})");
