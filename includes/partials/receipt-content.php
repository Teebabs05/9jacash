<?php
/**
 * Shared printable receipt body for a deposit or withdrawal.
 * Expects $receiptType ('deposit'|'withdrawal'), $record (the row) and
 * $ownerUser (the user the transaction belongs to) to be set before
 * including this partial.
 */
$isDeposit = $receiptType === 'deposit';
$receiptNumber = ($isDeposit ? 'DEP' : 'WD') . '-' . str_pad((string) $record['id'], 6, '0', STR_PAD_LEFT);
$statusColors = ['pending' => '#F2C94C', 'approved' => '#0F5132', 'rejected' => '#F04438'];
$statusColor = $statusColors[$record['status']] ?? '#8a94a6';
?>
<div class="receipt-print-area">
    <div class="card-surface p-4 p-md-5" style="max-width:680px;margin:0 auto;">
        <div class="d-flex justify-content-between align-items-start mb-4 pb-4" style="border-bottom:2px solid var(--border);">
            <div class="d-flex align-items-center gap-2">
                <?= brand_mark_html(44) ?>
                <div>
                    <div class="fw-bold fs-5"><?= e(get_setting('site_name', 'SURECASH MINING')) ?></div>
                    <div class="small" style="color:var(--text-muted);">Transaction Receipt</div>
                </div>
            </div>
            <div class="text-end">
                <div class="fw-bold"><?= e($receiptNumber) ?></div>
                <span class="pill" style="background:<?= e($statusColor) ?>22;color:<?= e($statusColor) ?>;border:1px solid <?= e($statusColor) ?>55;"><?= e(ucfirst($record['status'])) ?></span>
            </div>
        </div>

        <h4 class="fw-bold mb-4"><?= $isDeposit ? 'Deposit' : 'Withdrawal' ?> <?= e(money($record['amount'])) ?></h4>

        <div class="row g-3 mb-4">
            <div class="col-6"><span class="small" style="color:var(--text-muted);">Account Holder</span><div class="fw-semibold"><?= e($ownerUser['full_name']) ?></div></div>
            <div class="col-6"><span class="small" style="color:var(--text-muted);">Username</span><div class="fw-semibold">@<?= e($ownerUser['username']) ?></div></div>
            <div class="col-6"><span class="small" style="color:var(--text-muted);">Reference</span><div class="fw-semibold"><?= e($record['reference'] ?? $receiptNumber) ?></div></div>
            <div class="col-6"><span class="small" style="color:var(--text-muted);">Method</span><div class="fw-semibold text-uppercase"><?= e($record['method']) ?></div></div>
            <div class="col-6"><span class="small" style="color:var(--text-muted);">Date</span><div class="fw-semibold"><?= e(date('F j, Y g:i A', strtotime($record['created_at']))) ?></div></div>
            <div class="col-6"><span class="small" style="color:var(--text-muted);">Status</span><div class="fw-semibold"><?= e(ucfirst($record['status'])) ?></div></div>
        </div>

        <div class="p-3 mb-4" style="background:var(--surface-alt);border-radius:12px;">
            <div class="d-flex justify-content-between py-1">
                <span style="color:var(--text-muted);">Amount</span><strong><?= e(money($record['amount'])) ?></strong>
            </div>
            <?php if (!$isDeposit): ?>
                <div class="d-flex justify-content-between py-1">
                    <span style="color:var(--text-muted);">Charge</span><strong class="text-danger">-<?= e(money($record['charge'])) ?></strong>
                </div>
                <div class="d-flex justify-content-between py-1 pt-2 mt-1" style="border-top:1px solid var(--border);">
                    <span class="fw-bold">Net Paid Out</span><strong class="text-success"><?= e(money($record['net_amount'])) ?></strong>
                </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between py-1">
                <span style="color:var(--text-muted);">&asymp; USD</span><strong><?= e(money_usd((float) ($isDeposit ? $record['amount'] : $record['net_amount']))) ?></strong>
            </div>
        </div>

        <?php if (!empty($record['admin_note'])): ?>
            <div class="alert alert-secondary small mb-4"><strong>Note:</strong> <?= e($record['admin_note']) ?></div>
        <?php endif; ?>

        <p class="small text-center mb-0" style="color:var(--text-muted);">
            This is an automatically generated receipt from <?= e(get_setting('site_name', 'SURECASH MINING')) ?>.
            Generated on <?= e(date('F j, Y g:i A')) ?>.
        </p>
    </div>
</div>
