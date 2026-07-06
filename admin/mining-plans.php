<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$admin = current_admin();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = clean($_POST['name'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $dailyReturn = (float) ($_POST['daily_return'] ?? 0);
        $duration = (int) ($_POST['duration_days'] ?? 30);
        $description = clean($_POST['description'] ?? '');
        $status = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if (strlen($name) < 2) {
            $errors[] = 'Please enter a plan name.';
        }
        if ($price <= 0 || $dailyReturn <= 0 || $duration <= 0) {
            $errors[] = 'Price, daily return and duration must all be greater than zero.';
        }

        if (!$errors) {
            if ($id > 0) {
                db()->prepare('UPDATE mining_plans SET name = ?, price = ?, daily_return = ?, duration_days = ?, description = ?, status = ?, updated_at = NOW() WHERE id = ?')
                    ->execute([$name, $price, $dailyReturn, $duration, $description, $status, $id]);
                flash('mining_plans', 'Mining plan updated.', 'success');
            } else {
                db()->prepare('INSERT INTO mining_plans (name, price, daily_return, duration_days, description, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())')
                    ->execute([$name, $price, $dailyReturn, $duration, $description, $status]);
                flash('mining_plans', 'Mining plan created.', 'success');
            }
            log_activity(null, (int) $admin['id'], 'mining_plan_saved', "Saved mining plan: {$name}");
            redirect(rtrim(APP_URL, '/') . '/admin/mining-plans.php');
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $hasPositions = (int) db()->query('SELECT COUNT(*) AS c FROM user_mining WHERE plan_id = ' . $id)->fetch()['c'];

        if ($hasPositions > 0) {
            flash('mining_plans', 'Cannot delete a plan that users have already invested in. Deactivate it instead.', 'error');
        } else {
            db()->prepare('DELETE FROM mining_plans WHERE id = ?')->execute([$id]);
            flash('mining_plans', 'Mining plan deleted.', 'success');
        }
        redirect(rtrim(APP_URL, '/') . '/admin/mining-plans.php');
    } elseif ($action === 'toggle_status') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare("UPDATE mining_plans SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?")->execute([$id]);
        redirect(rtrim(APP_URL, '/') . '/admin/mining-plans.php');
    } elseif ($action === 'assign') {
        $planId = (int) ($_POST['plan_id'] ?? 0);
        $targetUsername = clean($_POST['username'] ?? '');

        $stmt = db()->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([strtolower($targetUsername), strtolower($targetUsername)]);
        $targetUser = $stmt->fetch();

        $stmt = db()->prepare("SELECT * FROM mining_plans WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch();

        if (!$targetUser) {
            flash('mining_plans', 'No user found with that username/email.', 'error');
        } elseif (!$plan) {
            flash('mining_plans', 'Please select a valid active plan.', 'error');
        } else {
            $startedAt = date('Y-m-d H:i:s');
            $nextPayoutAt = date('Y-m-d H:i:s', strtotime('+1 day'));
            $endsAt = date('Y-m-d H:i:s', strtotime('+' . (int) $plan['duration_days'] . ' days'));

            db()->prepare(
                'INSERT INTO user_mining (user_id, plan_id, amount_invested, total_earned, started_at, next_payout_at, ends_at, status, created_at)
                 VALUES (?, ?, ?, 0.00, ?, ?, ?, ?, NOW())'
            )->execute([$targetUser['id'], $planId, $plan['price'], $startedAt, $nextPayoutAt, $endsAt, MINING_STATUS_ACTIVE]);

            notify_user((int) $targetUser['id'], 'Mining Plan Assigned', "An administrator has gifted you the {$plan['name']} mining plan!", NOTIFY_TYPE_MINING);
            log_activity((int) $targetUser['id'], (int) $admin['id'], 'admin_mining_plan_assigned', "Assigned {$plan['name']} to user #{$targetUser['id']} at no charge");
            flash('mining_plans', "Plan assigned to {$targetUsername} successfully (no wallet charge).", 'success');
        }
        redirect(rtrim(APP_URL, '/') . '/admin/mining-plans.php');
    }
}

$editPlan = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM mining_plans WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $editPlan = $stmt->fetch() ?: null;
}

$plans = db()->query(
    'SELECT mp.*, (SELECT COUNT(*) FROM user_mining um WHERE um.plan_id = mp.id) AS position_count
     FROM mining_plans mp ORDER BY mp.price ASC'
)->fetchAll();

$activePlans = array_filter($plans, fn ($p) => $p['status'] === 'active');

$pageTitle = 'Mining Plans';
$activeNav = 'mining-plans';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<?php if ($errors): ?>
    <div class="alert alert-danger py-2 px-3 small mb-3">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card-surface p-4 mb-4">
            <h5 class="fw-bold mb-3"><?= $editPlan ? 'Edit Plan' : 'Create Plan' ?></h5>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($editPlan['id'] ?? 0) ?>">

                <div class="mb-3">
                    <label class="form-label small">Plan Name</label>
                    <input type="text" class="form-control" name="name" value="<?= e($editPlan['name'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Description</label>
                    <textarea class="form-control" name="description" rows="2"><?= e($editPlan['description'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Price (₦)</label>
                    <input type="number" step="0.01" min="0.01" class="form-control" name="price" value="<?= e((string) ($editPlan['price'] ?? '')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Daily Return (₦)</label>
                    <input type="number" step="0.01" min="0.01" class="form-control" name="daily_return" value="<?= e((string) ($editPlan['daily_return'] ?? '')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Duration (days)</label>
                    <input type="number" min="1" class="form-control" name="duration_days" value="<?= e((string) ($editPlan['duration_days'] ?? 30)) ?>" required>
                </div>
                <div class="mb-4">
                    <label class="form-label small">Status</label>
                    <select class="form-select" name="status">
                        <option value="active" <?= ($editPlan['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($editPlan['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-brand w-100"><?= $editPlan ? 'Save Changes' : 'Create Plan' ?></button>
                <?php if ($editPlan): ?><a href="mining-plans.php" class="btn btn-outline-brand w-100 mt-2">Cancel</a><?php endif; ?>
            </form>
        </div>

        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Assign Plan to User</h5>
            <p class="small" style="color:var(--text-muted);">Gifts an active mining position at no charge to the user's wallet.</p>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="assign">
                <div class="mb-3">
                    <label class="form-label small">Username or Email</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Plan</label>
                    <select class="form-select" name="plan_id" required>
                        <?php foreach ($activePlans as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?> (<?= e(money($p['price'])) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-outline-brand w-100">Assign Plan</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">All Mining Plans</h5>
            <?php if (!$plans): ?>
                <div class="text-center py-5" style="color:var(--text-muted);">No mining plans created yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ledger-table mb-0">
                        <thead><tr><th>Name</th><th>Price</th><th>Daily Return</th><th>Duration</th><th>Positions</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($plans as $p): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($p['name']) ?></td>
                                    <td><?= e(money($p['price'])) ?></td>
                                    <td><?= e(money($p['daily_return'])) ?></td>
                                    <td><?= (int) $p['duration_days'] ?> days</td>
                                    <td><?= (int) $p['position_count'] ?></td>
                                    <td>
                                        <form method="POST" action="" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                            <button type="submit" class="pill pill-<?= $p['status'] === 'active' ? 'approved' : 'rejected' ?> border-0" style="cursor:pointer;"><?= e(ucfirst($p['status'])) ?></button>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <a href="?edit=<?= (int) $p['id'] ?>" class="btn btn-outline-brand btn-sm"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this plan?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
