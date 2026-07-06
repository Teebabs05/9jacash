<div class="row g-3 mb-4">
    <?php
    $purses = [
        'main' => ['label' => 'Main Wallet', 'icon' => 'fa-wallet', 'color' => 'linear-gradient(135deg,#0D47A1,#1565C0)'],
        'bonus' => ['label' => 'Bonus Wallet', 'icon' => 'fa-gift', 'color' => 'linear-gradient(135deg,#FFC107,#ff8f00)'],
        'referral' => ['label' => 'Referral Wallet', 'icon' => 'fa-share-nodes', 'color' => 'linear-gradient(135deg,#7b61ff,#4b2fd6)'],
        'mining' => ['label' => 'Mining Wallet', 'icon' => 'fa-server', 'color' => 'linear-gradient(135deg,#00b894,#00816a)'],
        'task' => ['label' => 'Task Wallet', 'icon' => 'fa-list-check', 'color' => 'linear-gradient(135deg,#ff5e62,#c0392b)'],
    ];
    ?>
    <?php foreach ($purses as $key => $p): ?>
    <div class="col-md-6 col-xl-4">
        <div class="stat-tile">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="icon-badge" style="background:<?= $p['color'] ?>"><i class="fa-solid <?= $p['icon'] ?>"></i></span>
                <?php if ($key !== 'main'): ?>
                <button class="btn btn-sm btn-outline-brand rounded-pill" data-bs-toggle="modal" data-bs-target="#transferModal" data-wallet="<?= $key ?>" data-balance="<?= (float) $wallet[$key . '_balance'] ?>">Transfer</button>
                <?php endif; ?>
            </div>
            <div class="value"><?= money($wallet[$key . '_balance']) ?></div>
            <div class="label"><?= e($p['label']) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="surface-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Recent Activity</h6>
        <a href="<?= base_url('transactions') ?>" class="small">View full history</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Description</th><th>Wallet</th><th>Amount</th><th>Balance After</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $tx): ?>
                <tr>
                    <td><?= e($tx['description'] ?: ucfirst(str_replace('_',' ',$tx['category']))) ?></td>
                    <td><span class="badge bg-secondary-subtle text-secondary-emphasis text-capitalize"><?= e($tx['wallet_type']) ?></span></td>
                    <td class="<?= $tx['type'] === 'credit' ? 'text-success' : 'text-danger' ?> fw-semibold"><?= $tx['type'] === 'credit' ? '+' : '-' ?><?= money($tx['amount']) ?></td>
                    <td><?= money($tx['balance_after']) ?></td>
                    <td class="small text-muted-soft"><?= time_ago($tx['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= base_url('wallet/transfer') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="from_wallet" id="transferFromWallet">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Transfer to Main Wallet</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted-soft">Available balance: <strong id="transferAvailable">₦0.00</strong></p>
                    <label class="form-label small fw-semibold">Amount</label>
                    <input type="number" step="0.01" min="1" name="amount" id="transferAmount" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-brand rounded-pill px-4">Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php push_script('
document.getElementById("transferModal").addEventListener("show.bs.modal", function (e) {
    var btn = e.relatedTarget;
    var wallet = btn.getAttribute("data-wallet");
    var balance = parseFloat(btn.getAttribute("data-balance")).toFixed(2);
    document.getElementById("transferFromWallet").value = wallet;
    document.getElementById("transferAvailable").textContent = "₦" + balance;
    document.getElementById("transferAmount").max = balance;
});
'); ?>
