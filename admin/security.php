<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$admin = current_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = (string) ($_POST['action'] ?? '');
    $ip = trim((string) ($_POST['ip_address'] ?? ''));

    if ($action === 'unban' && filter_var($ip, FILTER_VALIDATE_IP)) {
        unban_ip($ip);
        log_activity(null, (int) $admin['id'], 'security_ip_unbanned', 'Unbanned IP address ' . $ip);
        flash('security', 'IP address ' . $ip . ' has been unblocked.', 'success');
    } elseif ($action === 'ban' && filter_var($ip, FILTER_VALIDATE_IP)) {
        ban_ip($ip, 'Manually blocked by administrator');
        log_activity(null, (int) $admin['id'], 'security_ip_banned', 'Manually blocked IP address ' . $ip);
        flash('security', 'IP address ' . $ip . ' has been blocked.', 'success');
    }

    redirect(rtrim(APP_URL, '/') . '/admin/security.php');
}

$blockedIps = db()->query(
    'SELECT * FROM blocked_ips ORDER BY updated_at DESC LIMIT 100'
)->fetchAll();

$recentEvents = db()->query(
    'SELECT * FROM security_events ORDER BY created_at DESC LIMIT 100'
)->fetchAll();

$eventCount24h = (int) db()->query(
    'SELECT COUNT(*) AS c FROM security_events WHERE created_at >= NOW() - INTERVAL 24 HOUR'
)->fetch()['c'];

$activeBanCount = (int) db()->query(
    'SELECT COUNT(*) AS c FROM blocked_ips WHERE blocked_until IS NULL OR blocked_until > NOW()'
)->fetch()['c'];

$eventTypeLabels = [
    'scanner_ua' => 'Scanner tool',
    'exploit_path' => 'Exploit-probe path',
    'waf_pattern' => 'Malicious payload',
    'banned_ip_retry' => 'Banned IP retry',
];

$pageTitle = 'Security & Firewall';
$activeNav = 'security';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<?php require __DIR__ . '/../includes/partials/flash-messages.php'; ?>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card-surface p-4">
            <div class="small" style="color:var(--text-muted);">Blocked IPs (active)</div>
            <div class="fs-3 fw-bold"><?= number_format($activeBanCount) ?></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card-surface p-4">
            <div class="small" style="color:var(--text-muted);">Firewall trips (24h)</div>
            <div class="fs-3 fw-bold"><?= number_format($eventCount24h) ?></div>
        </div>
    </div>
</div>

<div class="card-surface p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Blocked IP Addresses</h5>
        <form method="POST" action="" class="d-flex gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="ban">
            <input type="text" name="ip_address" class="form-control form-control-sm" placeholder="IP address to block" required style="width:180px;">
            <button type="submit" class="btn btn-outline-brand btn-sm">Block IP</button>
        </form>
    </div>
    <?php if (!$blockedIps): ?>
        <div class="text-center py-4" style="color:var(--text-muted);">No IP addresses are currently blocked.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table ledger-table mb-0">
                <thead><tr><th>IP Address</th><th>Reason</th><th>Strikes</th><th>Blocked Until</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($blockedIps as $b): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($b['ip_address']) ?></td>
                            <td class="small"><?= e($b['reason'] ?? '') ?></td>
                            <td><?= (int) $b['strikes'] ?></td>
                            <td class="small" style="color:var(--text-muted);">
                                <?= $b['blocked_until'] ? e(date('M d, Y H:i', strtotime($b['blocked_until']))) : 'Permanent' ?>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="" onsubmit="return confirm('Unblock <?= e($b['ip_address']) ?>?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="unban">
                                    <input type="hidden" name="ip_address" value="<?= e($b['ip_address']) ?>">
                                    <button type="submit" class="btn btn-outline-brand btn-sm">Unblock</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card-surface p-4">
    <h5 class="mb-3">Recent Firewall Activity</h5>
    <?php if (!$recentEvents): ?>
        <div class="text-center py-4" style="color:var(--text-muted);">No firewall activity recorded yet.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table ledger-table mb-0">
                <thead><tr><th>Time</th><th>IP Address</th><th>Type</th><th>Request URI</th><th>Detail</th></tr></thead>
                <tbody>
                    <?php foreach ($recentEvents as $ev): ?>
                        <tr>
                            <td class="small" style="color:var(--text-muted);"><?= e(date('M d, Y H:i', strtotime($ev['created_at']))) ?></td>
                            <td class="fw-semibold"><?= e($ev['ip_address']) ?></td>
                            <td><span class="pill pill-rejected"><?= e($eventTypeLabels[$ev['event_type']] ?? $ev['event_type']) ?></span></td>
                            <td class="small text-truncate" style="max-width:280px;" title="<?= e((string) $ev['request_uri']) ?>"><?= e((string) $ev['request_uri']) ?></td>
                            <td class="small"><?= e((string) $ev['detail']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
