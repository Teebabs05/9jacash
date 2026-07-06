<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$receiptType = ($_GET['type'] ?? '') === 'withdrawal' ? 'withdrawal' : 'deposit';
$id = (int) ($_GET['id'] ?? 0);

$table = $receiptType === 'deposit' ? 'deposits' : 'withdrawals';
$stmt = db()->prepare("SELECT * FROM {$table} WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    flash('receipts', 'Receipt not found.', 'error');
    redirect(rtrim(APP_URL, '/') . '/admin/' . ($receiptType === 'deposit' ? 'deposits.php' : 'withdrawals.php'));
}

$stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$record['user_id']]);
$ownerUser = $stmt->fetch();

$pageTitle = 'Receipt';
$activeNav = $receiptType === 'deposit' ? 'deposits' : 'withdrawals';
require __DIR__ . '/../includes/partials/admin-head.php';
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

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
