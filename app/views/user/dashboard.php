<?php $u = current_user(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-0">Welcome back, <?= e($u['full_name'] ?? $u['username']) ?> 👋</h3>
        <p class="text-muted-soft mb-0">Here's what's happening with your account today.</p>
    </div>
    <a href="<?= base_url('deposit') ?>" class="btn btn-brand rounded-pill px-4 d-none d-md-inline-flex align-items-center gap-2"><i class="fa-solid fa-circle-plus"></i> Deposit</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="stat-tile">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="icon-badge"><i class="fa-solid fa-wallet"></i></span>
            </div>
            <div class="value"><?= money($wallet['main_balance']) ?></div>
            <div class="label">Main Wallet</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-tile">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="icon-badge" style="background:linear-gradient(135deg,#FFC107,#ff8f00);"><i class="fa-solid fa-gift"></i></span>
            </div>
            <div class="value"><?= money($wallet['bonus_balance']) ?></div>
            <div class="label">Bonus Wallet</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-tile">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="icon-badge" style="background:linear-gradient(135deg,#00b894,#00816a);"><i class="fa-solid fa-server"></i></span>
            </div>
            <div class="value"><?= money($wallet['mining_balance']) ?></div>
            <div class="label">Mining Wallet</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-tile">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="icon-badge" style="background:linear-gradient(135deg,#7b61ff,#4b2fd6);"><i class="fa-solid fa-share-nodes"></i></span>
            </div>
            <div class="value"><?= money($wallet['referral_balance']) ?></div>
            <div class="label">Referral Wallet</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="surface-card p-3 d-flex align-items-center gap-3">
            <span class="icon-badge"><i class="fa-solid fa-sack-dollar"></i></span>
            <div><div class="fw-bold fs-5"><?= money($wallet['total_earned']) ?></div><div class="small text-muted-soft">Total Earnings</div></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="surface-card p-3 d-flex align-items-center gap-3">
            <span class="icon-badge" style="background:linear-gradient(135deg,#ff9800,#e65100);"><i class="fa-solid fa-hourglass-half"></i></span>
            <div><div class="fw-bold fs-5"><?= money($wallet['pending_earned']) ?></div><div class="small text-muted-soft">Pending Earnings</div></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="surface-card p-3 d-flex align-items-center gap-3">
            <span class="icon-badge" style="background:linear-gradient(135deg,#0D47A1,#1565C0);"><i class="fa-solid fa-coins"></i></span>
            <div><div class="fw-bold fs-5"><?= money($wallet['main_balance']) ?></div><div class="small text-muted-soft">Withdrawable Balance</div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="surface-card p-4 mb-3">
            <h6 class="fw-bold mb-3">Earnings — Last 7 Days</h6>
            <canvas id="earningsChart" height="130"></canvas>
        </div>
        <div class="surface-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Recent Transactions</h6>
                <a href="<?= base_url('transactions') ?>" class="small">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Description</th><th>Wallet</th><th>Amount</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentTx as $tx): ?>
                        <tr>
                            <td><?= e($tx['description'] ?: ucfirst(str_replace('_',' ',$tx['category']))) ?></td>
                            <td><span class="badge bg-secondary-subtle text-secondary-emphasis text-capitalize"><?= e($tx['wallet_type']) ?></span></td>
                            <td class="<?= $tx['type'] === 'credit' ? 'text-success' : 'text-danger' ?> fw-semibold">
                                <?= $tx['type'] === 'credit' ? '+' : '-' ?><?= money($tx['amount']) ?>
                            </td>
                            <td class="small text-muted-soft"><?= time_ago($tx['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentTx)): ?>
                        <tr><td colspan="4" class="text-center text-muted-soft py-4">No transactions yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="surface-card p-4 mb-3">
            <h6 class="fw-bold mb-3">Active Mining Plans</h6>
            <?php foreach ($activeMining as $m): ?>
                <div class="d-flex justify-content-between align-items-center border rounded-3 p-2 mb-2" style="border-color:var(--border-color) !important;">
                    <div>
                        <div class="fw-semibold"><?= e($m['plan_name']) ?></div>
                        <div class="small text-muted-soft">Day <?= (int) $m['days_completed'] ?> / <?= (int) $m['duration_days'] ?></div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-success"><?= money($m['daily_profit']) ?>/day</div>
                        <div class="small text-muted-soft"><?= e(ucfirst($m['status'])) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($activeMining)): ?>
                <p class="text-muted-soft small">You have no active mining plans.</p>
                <a href="<?= base_url('mining') ?>" class="btn btn-outline-brand rounded-pill w-100">Browse Mining Plans</a>
            <?php endif; ?>
        </div>
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">Referral Snapshot</h6>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-soft">Total Referrals</span><strong><?= (int) $referralCount ?></strong></div>
            <div class="d-flex justify-content-between mb-3"><span class="text-muted-soft">Referral Earnings</span><strong><?= money($referralEarnings) ?></strong></div>
            <a href="<?= base_url('referrals') ?>" class="btn btn-brand rounded-pill w-100">View Referral Dashboard</a>
        </div>
    </div>
</div>

<?php push_script('
new Chart(document.getElementById("earningsChart"), {
    type: "line",
    data: {
        labels: ' . json_encode($chartLabels) . ',
        datasets: [{ label: "Earnings", data: ' . json_encode($chartData) . ', borderColor: "#1565C0", backgroundColor: "rgba(13,71,161,.15)", tension: .35, fill: true }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
'); ?>
