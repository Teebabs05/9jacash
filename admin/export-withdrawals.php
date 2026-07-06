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
    $where[] = 'w.status = ?';
    $params[] = $statusFilter;
}
if ($from !== '') {
    $where[] = 'w.created_at >= ?';
    $params[] = $from . ' 00:00:00';
}
if ($to !== '') {
    $where[] = 'w.created_at <= ?';
    $params[] = $to . ' 23:59:59';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = db()->prepare(
    "SELECT w.*, u.username, u.full_name, u.email
     FROM withdrawals w
     INNER JOIN users u ON u.id = w.user_id
     {$whereSql}
     ORDER BY w.created_at DESC"
);
$stmt->execute($params);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="withdrawals-' . date('Y-m-d-His') . '.csv"');
header('X-Content-Type-Options: nosniff');

$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'Username', 'Full Name', 'Email', 'Method', 'Amount', 'Charge', 'Net Amount', 'Account Details', 'Status', 'Admin Note', 'Created At', 'Processed At'], ',', '"', '\\');

while ($w = $stmt->fetch()) {
    fputcsv($out, [
        $w['id'],
        $w['username'],
        $w['full_name'],
        $w['email'],
        $w['method'],
        sprintf('%.2f', (float) $w['amount']),
        sprintf('%.2f', (float) $w['charge']),
        sprintf('%.2f', (float) $w['net_amount']),
        $w['account_details'],
        $w['status'],
        $w['admin_note'],
        $w['created_at'],
        $w['processed_at'],
    ], ',', '"', '\\');
}

fclose($out);

log_activity(null, (int) current_admin()['id'], 'admin_export_withdrawals', "Exported withdrawals CSV (status={$statusFilter})");
