<div class="row g-3 mb-4">
    <?php
    $tiles = [
        ['label' => 'Total Users', 'value' => number_format($stats['total_users']), 'icon' => 'fa-users', 'color' => 'linear-gradient(135deg,#0D47A1,#1565C0)'],
        ['label' => 'Active Users', 'value' => number_format($stats['active_users']), 'icon' => 'fa-user-check', 'color' => 'linear-gradient(135deg,#00b894,#00816a)'],
        ['label' => 'Pending Users', 'value' => number_format($stats['pending_users']), 'icon' => 'fa-user-clock', 'color' => 'linear-gradient(135deg,#ff9800,#e65100)'],
        ['label' => "Today's Signups", 'value' => number_format($stats['todays_registrations']), 'icon' => 'fa-user-plus', 'color' => 'linear-gradient(135deg,#7b61ff,#4b2fd6)'],
    ];
    ?>
    <?php foreach ($tiles as $t): ?>
    <div class="col-md-6 col-xl-3">
        <div class="stat-tile">
            <span class="icon-badge mb-2" style="background:<?= $t['color'] ?>"><i class="fa-solid <?= $t['icon'] ?>"></i></span>
            <div class="value"><?= $t['value'] ?></div>
            <div class="label"><?= $t['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <?php
    $tiles2 = [
        ['label' => 'Total Deposits', 'value' => money($stats['total_deposits']), 'icon' => 'fa-circle-plus', 'color' => '#0D47A1'],
        ['label' => 'Pending Deposits', 'value' => number_format($stats['pending_deposits']), 'icon' => 'fa-hourglass-half', 'color' => '#ff9800'],
        ['label' => 'Total Withdrawals', 'value' => money($stats['total_withdrawals']), 'icon' => 'fa-money-bill-transfer', 'color' => '#e53935'],
        ['label' => 'Pending Withdrawals', 'value' => number_format($stats['pending_withdrawals']), 'icon' => 'fa-clock', 'color' => '#ff9800'],
        ['label' => 'Mining Volume', 'value' => money($stats['mining_income']), 'icon' => 'fa-server', 'color' => '#00b894'],
        ['label' => 'Referral Earnings', 'value' => money($stats['referral_earnings']), 'icon' => 'fa-share-nodes', 'color' => '#7b61ff'],
        ['label' => 'Task Earnings', 'value' => money($stats['task_earnings']), 'icon' => 'fa-list-check', 'color' => '#ff5e62'],
        ['label' => 'System Revenue', 'value' => money($stats['system_revenue']), 'icon' => 'fa-sack-dollar', 'color' => '#0D47A1'],
    ];
    ?>
    <?php foreach ($tiles2 as $t): ?>
    <div class="col-md-6 col-xl-3">
        <div class="surface-card p-3 d-flex align-items-center gap-3">
            <span class="icon-badge" style="background:<?= $t['color'] ?>"><i class="fa-solid <?= $t['icon'] ?>"></i></span>
            <div><div class="fw-bold"><?= $t['value'] ?></div><div class="small text-muted-soft"><?= $t['label'] ?></div></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="surface-card p-4 mb-3">
            <h6 class="fw-bold mb-3">New Registrations — Last 14 Days</h6>
            <canvas id="regChart" height="110"></canvas>
        </div>
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">Latest Transactions</h6>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>User</th><th>Description</th><th>Amount</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($latestTx as $tx): ?>
                        <tr>
                            <td><?= e($tx['username']) ?></td>
                            <td><?= e($tx['description'] ?: ucfirst(str_replace('_',' ',$tx['category']))) ?></td>
                            <td class="<?= $tx['type'] === 'credit' ? 'text-success' : 'text-danger' ?> fw-semibold"><?= $tx['type'] === 'credit' ? '+' : '-' ?><?= money($tx['amount']) ?></td>
                            <td class="small text-muted-soft"><?= time_ago($tx['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="surface-card p-4 mb-3">
            <h6 class="fw-bold mb-3">Recent Logins</h6>
            <?php foreach ($recentLogins as $l): ?>
                <div class="d-flex justify-content-between border-bottom py-2 small" style="border-color:var(--border-color) !important;">
                    <span><?= e($l['username']) ?></span>
                    <span class="text-muted-soft"><?= e($l['ip_address']) ?> &middot; <?= time_ago($l['created_at']) ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($recentLogins)): ?><p class="text-muted-soft small text-center py-3">No login activity yet.</p><?php endif; ?>
        </div>
        <div class="surface-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Support Tickets</h6>
                <span class="badge bg-danger-subtle text-danger-emphasis"><?= $stats['open_tickets'] ?> open</span>
            </div>
            <?php foreach ($recentTickets as $t): ?>
                <a href="<?= base_url('admin/support/' . $t['id']) ?>" class="d-flex justify-content-between border-bottom py-2 small text-decoration-none" style="border-color:var(--border-color) !important; color:var(--text-primary);">
                    <span><?= e($t['username']) ?>: <?= e($t['subject']) ?></span>
                    <span class="badge badge-status-<?= $t['status'] === 'closed' ? 'suspended' : 'pending' ?> text-capitalize"><?= e($t['status']) ?></span>
                </a>
            <?php endforeach; ?>
            <?php if (empty($recentTickets)): ?><p class="text-muted-soft small text-center py-3">No tickets yet.</p><?php endif; ?>
        </div>
    </div>
</div>

<?php push_script('
new Chart(document.getElementById("regChart"), {
    type: "bar",
    data: { labels: ' . json_encode($chartLabels) . ', datasets: [{ label: "Signups", data: ' . json_encode($chartData) . ', backgroundColor: "#1565C0", borderRadius: 6 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});
'); ?>
