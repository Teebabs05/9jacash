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

$cycles = mining_plan_cycles($plan);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $cycleDays = (int) ($_POST['cycle_days'] ?? 0);
    $result = mining_purchase_plan((int) $user['id'], (int) $plan['id'], $cycleDays);

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

                    <label class="form-label small">Choose Your Mining Cycle</label>
                    <div class="d-flex gap-2 flex-wrap mb-3" id="cycleOptions">
                        <?php foreach ($cycles as $i => $cycle): ?>
                            <div class="form-check form-check-inline border rounded px-3 py-2" style="border-color:var(--border) !important;">
                                <input class="form-check-input" type="radio" name="cycle_days" id="cycle<?= (int) $cycle ?>" value="<?= (int) $cycle ?>" data-daily="<?= e((string) $plan['daily_return']) ?>" <?= $i === 0 ? 'checked' : '' ?> required>
                                <label class="form-check-label" for="cycle<?= (int) $cycle ?>"><?= (int) $cycle ?> days</label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);">
                        <span style="color:var(--text-muted);">Total Return</span><strong id="cycleTotalReturn"><?= e(money($plan['daily_return'] * $cycles[0])) ?></strong>
                    </div>

                    <button type="submit" class="btn btn-brand w-100 mt-3">Confirm &amp; Invest <?= e(money($plan['price'])) ?></button>
                    <a href="index.php" class="btn btn-outline-brand w-100 mt-2">Cancel</a>
                </form>

                <script>
                    (function () {
                        var radios = document.querySelectorAll('#cycleOptions input[name="cycle_days"]');
                        var totalEl = document.getElementById('cycleTotalReturn');
                        radios.forEach(function (r) {
                            r.addEventListener('change', function () {
                                var daily = parseFloat(r.getAttribute('data-daily')) || 0;
                                var days = parseInt(r.value, 10) || 0;
                                totalEl.textContent = '<?= e(get_setting('currency_symbol', '₦')) ?>' + (daily * days).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            });
                        });
                    })();
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
