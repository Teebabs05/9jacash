<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();

$walletType = $_GET['wallet_type'] ?? '';
$source = $_GET['source'] ?? '';
$type = $_GET['type'] ?? '';
$dateFrom = $_GET['from'] ?? '';
$dateTo = $_GET['to'] ?? '';

$where = ['user_id = ?'];
$params = [$user['id']];

if (in_array($walletType, WALLET_TYPES, true)) {
    $where[] = 'wallet_type = ?';
    $params[] = $walletType;
}

$validSources = ['deposit', 'withdrawal', 'mining', 'task', 'ad', 'spin', 'checkin', 'referral', 'admin_adjustment', 'transfer'];
if (in_array($source, $validSources, true)) {
    $where[] = 'source = ?';
    $params[] = $source;
}

if (in_array($type, [LEDGER_CREDIT, LEDGER_DEBIT], true)) {
    $where[] = 'type = ?';
    $params[] = $type;
}

if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $where[] = 'created_at >= ?';
    $params[] = $dateFrom . ' 00:00:00';
}

if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $where[] = 'created_at <= ?';
    $params[] = $dateTo . ' 23:59:59';
}

$whereSql = implode(' AND ', $where);

$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countStmt = db()->prepare("SELECT COUNT(*) AS c FROM wallet_ledger WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['c'];
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = db()->prepare(
    "SELECT * FROM wallet_ledger WHERE {$whereSql} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$sourceLabels = [
    'deposit' => 'Deposit',
    'withdrawal' => 'Withdrawal',
    'mining' => 'Mining',
    'task' => 'Task Reward',
    'ad' => 'Ad Reward',
    'spin' => 'Spin Wheel',
    'checkin' => 'Daily Check-in',
    'referral' => 'Referral Bonus',
    'admin_adjustment' => 'Admin Adjustment',
    'transfer' => 'Transfer',
];

function keep_query(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    unset($params['page']);
    return http_build_query(array_filter($params, fn ($v) => $v !== ''));
}

$pageTitle = 'Transaction History';
$activeNav = 'history';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<div class="card-surface p-4 mb-4">
    <form method="GET" action="" class="row g-3">
        <div class="col-md-3">
            <label class="form-label small">Wallet</label>
            <select name="wallet_type" class="form-select form-select-sm">
                <option value="">All Wallets</option>
                <?php foreach (WALLET_TYPES as $wt): ?>
                    <option value="<?= e($wt) ?>" <?= $walletType === $wt ? 'selected' : '' ?>><?= e(ucfirst($wt)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Source</label>
            <select name="source" class="form-select form-select-sm">
                <option value="">All Sources</option>
                <?php foreach ($sourceLabels as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $source === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Type</label>
            <select name="type" class="form-select form-select-sm">
                <option value="">Credit &amp; Debit</option>
                <option value="credit" <?= $type === 'credit' ? 'selected' : '' ?>>Credit</option>
                <option value="debit" <?= $type === 'debit' ? 'selected' : '' ?>>Debit</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">From</label>
            <input type="date" name="from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">To</label>
            <input type="date" name="to" class="form-control form-control-sm" value="<?= e($dateTo) ?>">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-brand btn-sm">Filter</button>
            <a href="history.php" class="btn btn-outline-brand btn-sm">Reset</a>
        </div>
    </form>
</div>

<div class="card-surface p-4">
    <?php if (!$rows): ?>
        <div class="text-center py-5" style="color:var(--text-muted);">
            <i class="bi bi-receipt" style="font-size:2rem;"></i>
            <p class="mt-2 mb-0">No transactions match your filters.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table ledger-table mb-0">
                <thead>
                    <tr><th>Description</th><th>Wallet</th><th>Type</th><th>Amount</th><th>Balance After</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row):
                        $rowDescription = $row['description'] ?: ($sourceLabels[$row['source']] ?? ucfirst($row['source']));
                    ?>
                        <tr data-ledger-row
                            data-ledger-description="<?= e($rowDescription) ?>"
                            data-ledger-wallet="<?= e($row['wallet_type']) ?>"
                            data-ledger-type="<?= e(ucfirst($row['type'])) ?>"
                            data-ledger-amount="<?= e(($row['type'] === 'credit' ? '+' : '-') . money($row['amount'])) ?>"
                            data-ledger-balance="<?= e(money($row['balance_after'])) ?>"
                            data-ledger-status="<?= e(ucfirst($row['status'])) ?>"
                            data-ledger-reference="<?= e($row['reference'] ?: '-') ?>"
                            data-ledger-date="<?= e(date('M d, Y H:i', strtotime($row['created_at']))) ?>">
                            <td><?= e($rowDescription) ?></td>
                            <td class="text-capitalize"><?= e($row['wallet_type']) ?></td>
                            <td><span class="pill pill-<?= $row['type'] === 'credit' ? 'credit' : 'debit' ?>"><?= e(ucfirst($row['type'])) ?></span></td>
                            <td class="fw-semibold <?= $row['type'] === 'credit' ? 'text-success' : 'text-danger' ?>">
                                <?= $row['type'] === 'credit' ? '+' : '-' ?><?= e(money($row['amount'])) ?>
                            </td>
                            <td><?= e(money($row['balance_after'])) ?></td>
                            <td class="small" style="color:var(--text-muted);"><?= e(date('M d, Y H:i', strtotime($row['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= e(keep_query(['page' => $i])) ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require __DIR__ . '/../includes/partials/transaction-detail-modal.php'; ?>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
