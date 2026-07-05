<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();
$planId = (int) ($_GET['plan'] ?? $_POST['plan_id'] ?? 0);

$stmt = db()->prepare("SELECT * FROM mining_plans WHERE id = ? AND status = 'active' LIMIT 1");
$stmt->execute([$planId]);
$plan = $stmt->fetch();

if (!$plan) {
    flash('mining', 'That mining plan is not available.', 'error');
    redirect(rtrim(APP_URL, '/') . '/mining/index.php');
}

$wallet = get_wallet((int) $user['id']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $result = mining_purchase_plan((int) $user['id'], (int) $plan['id']);

    if ($result['success']) {
        flash('mining', $result['message'], 'success');
        redirect(rtrim(APP_URL, '/') . '/mining/index.php');
    }

    $errors[] = $result['message'];
}

$pageTitle = 'Invest in ' . $plan['name'];
$activeNav = 'mining';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Confirm Your Investment</h5>

            <?php if ($errors): ?>
                <div class="alert alert-danger py-2 px-3 small mb-3">
                    <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);">
                <span style="color:var(--text-muted);">Plan</span><strong><?= e($plan['name']) ?></strong>
            </div>
            <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);">
                <span style="color:var(--text-muted);">Investment Amount</span><strong><?= e(money($plan['price'])) ?></strong>
            </div>
            <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);">
                <span style="color:var(--text-muted);">Daily Return</span><strong class="text-success"><?= e(money($plan['daily_return'])) ?></strong>
            </div>
            <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);">
                <span style="color:var(--text-muted);">Duration</span><strong><?= (int) $plan['duration_days'] ?> days</strong>
            </div>
            <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);">
                <span style="color:var(--text-muted);">Total Return</span><strong><?= e(money($plan['daily_return'] * $plan['duration_days'])) ?></strong>
            </div>
            <div class="d-flex justify-content-between py-2 mb-3">
                <span style="color:var(--text-muted);">Your Main Wallet Balance</span>
                <strong class="<?= (float) $wallet['main_balance'] < (float) $plan['price'] ? 'text-danger' : '' ?>"><?= e(money($wallet['main_balance'])) ?></strong>
            </div>

            <?php if ((float) $wallet['main_balance'] < (float) $plan['price']): ?>
                <div class="alert alert-warning small py-2 px-3">Your main wallet balance is insufficient for this plan. Please deposit funds first.</div>
                <a href="index.php" class="btn btn-outline-brand w-100">Back to Plans</a>
            <?php else: ?>
                <form method="POST" action="" data-loading-submit>
                    <?= csrf_field() ?>
                    <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
                    <button type="submit" class="btn btn-brand w-100">Confirm &amp; Invest <?= e(money($plan['price'])) ?></button>
                    <a href="index.php" class="btn btn-outline-brand w-100 mt-2">Cancel</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
