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
        $label = clean($_POST['label'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $probability = (float) ($_POST['probability'] ?? 0);
        $color = clean($_POST['color'] ?? '#0F5132');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($label === '') {
            $errors[] = 'Please enter a segment label.';
        }
        if ($amount < 0) {
            $errors[] = 'Reward amount cannot be negative.';
        }
        if ($probability <= 0) {
            $errors[] = 'Probability weight must be greater than zero.';
        }

        if (!$errors) {
            if ($id > 0) {
                db()->prepare('UPDATE spin_settings SET label = ?, amount = ?, probability = ?, color = ?, is_active = ? WHERE id = ?')
                    ->execute([$label, $amount, $probability, $color, $isActive, $id]);
                flash('spin', 'Segment updated.', 'success');
            } else {
                db()->prepare('INSERT INTO spin_settings (label, amount, probability, color, is_active, created_at) VALUES (?, ?, ?, ?, ?, NOW())')
                    ->execute([$label, $amount, $probability, $color, $isActive]);
                flash('spin', 'Segment added.', 'success');
            }
            log_activity(null, (int) $admin['id'], 'spin_settings_saved', "Saved spin segment: {$label}");
            redirect(rtrim(APP_URL, '/') . '/admin/spin-settings.php');
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM spin_settings WHERE id = ?')->execute([$id]);
        flash('spin', 'Segment deleted.', 'success');
        redirect(rtrim(APP_URL, '/') . '/admin/spin-settings.php');
    } elseif ($action === 'update_daily_limit') {
        $limit = max(1, (int) ($_POST['spin_daily_limit'] ?? 1));
        set_setting('spin_daily_limit', (string) $limit);
        flash('spin', 'Daily spin limit updated.', 'success');
        redirect(rtrim(APP_URL, '/') . '/admin/spin-settings.php');
    }
}

$editSegment = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM spin_settings WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $editSegment = $stmt->fetch() ?: null;
}

$segments = db()->query('SELECT * FROM spin_settings ORDER BY id ASC')->fetchAll();
$totalWeight = array_sum(array_map(fn ($s) => (float) $s['probability'], array_filter($segments, fn ($s) => $s['is_active'])));

$pageTitle = 'Spin Wheel Settings';
$activeNav = 'spin-settings';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<?php if ($errors): ?>
    <div class="alert alert-danger py-2 px-3 small mb-3">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="card-surface p-4 mb-4">
    <h5 class="fw-bold mb-3">Daily Spin Limit</h5>
    <form method="POST" action="" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_daily_limit">
        <div class="col-auto">
            <label class="form-label small">Spins allowed per user per day</label>
            <input type="number" min="1" class="form-control" name="spin_daily_limit" value="<?= e((string) get_setting('spin_daily_limit', 1)) ?>">
        </div>
        <div class="col-auto"><button type="submit" class="btn btn-brand">Save</button></div>
    </form>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3"><?= $editSegment ? 'Edit Segment' : 'Add Segment' ?></h5>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($editSegment['id'] ?? 0) ?>">

                <div class="mb-3">
                    <label class="form-label small">Label</label>
                    <input type="text" class="form-control" name="label" value="<?= e($editSegment['label'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Reward Amount (₦, 0 = "Try Again")</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="amount" value="<?= e((string) ($editSegment['amount'] ?? '0')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Probability Weight</label>
                    <input type="number" step="0.01" min="0.01" class="form-control" name="probability" value="<?= e((string) ($editSegment['probability'] ?? '10')) ?>" required>
                    <div class="form-text">Weight relative to other active segments (not a fixed percentage).</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Color</label>
                    <input type="color" class="form-control form-control-color" name="color" value="<?= e($editSegment['color'] ?? '#0F5132') ?>">
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?= ($editSegment['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="is_active">Active</label>
                </div>

                <button type="submit" class="btn btn-brand w-100"><?= $editSegment ? 'Save Changes' : 'Add Segment' ?></button>
                <?php if ($editSegment): ?><a href="spin-settings.php" class="btn btn-outline-brand w-100 mt-2">Cancel</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Wheel Segments</h5>
            <div class="table-responsive">
                <table class="table ledger-table mb-0">
                    <thead><tr><th>Label</th><th>Reward</th><th>Weight</th><th>Odds</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($segments as $s): ?>
                            <tr>
                                <td class="fw-semibold"><span class="d-inline-block rounded-circle me-2" style="width:12px;height:12px;background:<?= e($s['color']) ?>;"></span><?= e($s['label']) ?></td>
                                <td><?= e(money($s['amount'])) ?></td>
                                <td><?= e((string) $s['probability']) ?></td>
                                <td><?= $s['is_active'] && $totalWeight > 0 ? number_format($s['probability'] / $totalWeight * 100, 1) . '%' : '—' ?></td>
                                <td><span class="pill pill-<?= $s['is_active'] ? 'active' : 'rejected' ?>"><?= $s['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                                <td class="text-end">
                                    <a href="?edit=<?= (int) $s['id'] ?>" class="btn btn-outline-brand btn-sm"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this segment?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
