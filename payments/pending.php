<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();
$depositId = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare("SELECT * FROM deposits WHERE id = ? AND user_id = ? AND method = 'payvessel' LIMIT 1");
$stmt->execute([$depositId, $user['id']]);
$deposit = $stmt->fetch();

if (!$deposit) {
    flash('deposit', 'Deposit not found.', 'error');
    redirect(rtrim(APP_URL, '/') . '/payments/deposit.php');
}

$gateway = json_decode((string) $deposit['gateway_response'], true) ?? [];
$bank = $gateway['banks'][0] ?? [];

$pageTitle = 'Complete Your Deposit';
$activeNav = 'deposit';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card-surface p-4 text-center">
            <?php if ($deposit['status'] === STATUS_APPROVED): ?>
                <div class="mb-3" style="font-size:3rem;color:var(--success);"><i class="bi bi-check-circle-fill"></i></div>
                <h5 class="fw-bold">Deposit Confirmed!</h5>
                <p class="small" style="color:var(--text-muted);"><?= e(money($deposit['amount'])) ?> has been credited to your wallet.</p>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/wallet/index.php" class="btn btn-brand w-100">Go to Wallet</a>
            <?php else: ?>
                <h5 class="fw-bold mb-1">Transfer to Complete Your Deposit</h5>
                <p class="small mb-4" style="color:var(--text-muted);">Send exactly <strong><?= e(money($deposit['amount'])) ?></strong> to the account below. Your wallet will be credited automatically once payment is confirmed.</p>

                <div class="text-start card-surface p-3 mb-4" style="background:var(--surface-alt);">
                    <div class="d-flex justify-content-between py-1"><span style="color:var(--text-muted);">Bank</span><strong><?= e($bank['bankName'] ?? '—') ?></strong></div>
                    <div class="d-flex justify-content-between py-1"><span style="color:var(--text-muted);">Account Number</span><strong id="accNumber"><?= e($bank['accountNumber'] ?? '—') ?></strong></div>
                    <div class="d-flex justify-content-between py-1"><span style="color:var(--text-muted);">Account Name</span><strong><?= e($bank['accountName'] ?? '—') ?></strong></div>
                    <div class="d-flex justify-content-between py-1"><span style="color:var(--text-muted);">Reference</span><strong><?= e($deposit['reference']) ?></strong></div>
                </div>

                <button type="button" class="btn btn-outline-brand w-100 mb-2" onclick="navigator.clipboard.writeText(document.getElementById('accNumber').textContent); SureCashMining.toast('Account number copied!');">Copy Account Number</button>

                <div id="statusMessage" class="alert alert-info small py-2 px-3">
                    <span class="spinner-border spinner-border-sm me-1"></span> Waiting for payment confirmation...
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($deposit['status'] !== STATUS_APPROVED): ?>
<script>
(function () {
    const depositId = <?= (int) $deposit['id'] ?>;
    const statusUrl = '<?= e(rtrim(APP_URL, '/')) ?>/ajax/deposit-status.php?id=' + depositId;

    const poll = setInterval(function () {
        fetch(statusUrl)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'approved') {
                    clearInterval(poll);
                    window.location.reload();
                } else if (data.status === 'rejected') {
                    clearInterval(poll);
                    document.getElementById('statusMessage').className = 'alert alert-danger small py-2 px-3';
                    document.getElementById('statusMessage').textContent = 'This deposit was not approved. Please contact support.';
                }
            })
            .catch(() => {});
    }, 6000);
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
