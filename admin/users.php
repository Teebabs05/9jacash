<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$search = trim((string) ($_GET['q'] ?? ''));
$statusFilter = $_GET['status'] ?? 'all';
if (!in_array($statusFilter, ['all', 'active', 'suspended', 'banned'], true)) {
    $statusFilter = 'all';
}

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ? OR u.referral_code LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}

if ($statusFilter !== 'all') {
    $where[] = 'u.status = ?';
    $params[] = $statusFilter;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countStmt = db()->prepare("SELECT COUNT(*) AS c FROM users u {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['c'];
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = db()->prepare(
    "SELECT u.*, w.main_balance, w.bonus_balance, w.referral_balance, w.mining_balance
     FROM users u
     LEFT JOIN wallets w ON w.user_id = u.id
     {$whereSql}
     ORDER BY u.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$users = $stmt->fetchAll();

function users_keep_query(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    unset($params['page']);
    return http_build_query(array_filter($params, fn ($v) => $v !== ''));
}

$pageTitle = 'Manage Users';
$activeNav = 'users';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<div class="card-surface p-4 mb-4">
    <form method="GET" action="" class="row g-2">
        <div class="col-md-5">
            <input type="text" class="form-control" name="q" value="<?= e($search) ?>" placeholder="Search by name, username, email or referral code">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                <option value="banned" <?= $statusFilter === 'banned' ? 'selected' : '' ?>>Banned</option>
            </select>
        </div>
        <div class="col-md-2"><button type="submit" class="btn btn-brand w-100">Search</button></div>
        <div class="col-md-2"><a href="users.php" class="btn btn-outline-brand w-100">Reset</a></div>
    </form>
</div>

<div class="card-surface p-4">
    <?php if (!$users): ?>
        <div class="text-center py-5" style="color:var(--text-muted);">No users found.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table ledger-table mb-0">
                <thead><tr><th>User</th><th>Email</th><th>Wallet Balance</th><th>KYC</th><th>Status</th><th>Joined</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= e($u['full_name']) ?><div class="small" style="color:var(--text-muted);">@<?= e($u['username']) ?></div></td>
                            <td class="small"><?= e($u['email']) ?></td>
                            <td><?= e(money((float) $u['main_balance'] + (float) $u['bonus_balance'] + (float) $u['referral_balance'] + (float) $u['mining_balance'])) ?></td>
                            <td><span class="pill pill-<?= $u['kyc_status'] === 'approved' ? 'approved' : ($u['kyc_status'] === 'rejected' ? 'rejected' : 'pending') ?>"><?= e(ucfirst($u['kyc_status'])) ?></span></td>
                            <td><span class="pill pill-<?= $u['status'] === 'active' ? 'active' : 'rejected' ?>"><?= e(ucfirst($u['status'])) ?></span></td>
                            <td class="small" style="color:var(--text-muted);"><?= e(date('M d, Y', strtotime($u['created_at']))) ?></td>
                            <td class="text-end"><a href="user-view.php?id=<?= (int) $u['id'] ?>" class="btn btn-outline-brand btn-sm">Manage</a></td>
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
                <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= e(users_keep_query(['page' => $i])) ?>&page=<?= $i ?>"><?= $i ?></a></li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
