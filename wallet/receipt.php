<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();
$receiptType = ($_GET['type'] ?? '') === 'withdrawal' ? 'withdrawal' : 'deposit';
$id = (int) ($_GET['id'] ?? 0);

$table = $receiptType === 'deposit' ? 'deposits' : 'withdrawals';
$stmt = db()->prepare("SELECT * FROM {$table} WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$id, $user['id']]);
$record = $stmt->fetch();

if (!$record) {
    flash('wallet', 'Receipt not found.', 'error');
    redirect(rtrim(APP_URL, '/') . '/wallet/history.php');
}

$ownerUser = $user;

$pageTitle = 'Receipt';
$activeNav = 'history';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 receipt-toolbar">
    <button type="button" onclick="history.back()" class="btn btn-outline-brand btn-sm"><i class="bi bi-arrow-left"></i> Back</button>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-brand btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print / Download PDF</button>
        <button type="button" class="btn btn-outline-brand btn-sm" id="shareReceiptBtn"><i class="bi bi-share"></i> Share</button>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/receipt-content.php'; ?>

<script>
document.getElementById('shareReceiptBtn').addEventListener('click', function () {
    const url = window.location.href;
    if (navigator.share) {
        navigator.share({ title: 'Transaction Receipt', url }).catch(function () {});
    } else {
        navigator.clipboard.writeText(url);
        SureCashMining.toast('Receipt link copied to clipboard!');
    }
});
</script>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
