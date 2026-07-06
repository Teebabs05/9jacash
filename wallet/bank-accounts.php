<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $type = ($_POST['type'] ?? 'bank') === 'usdt' ? 'usdt' : 'bank';

        if ($type === 'bank') {
            $bankName = clean($_POST['bank_name'] ?? '');
            $accountNumber = clean($_POST['account_number'] ?? '');
            $accountName = clean($_POST['account_name'] ?? '');

            if ($bankName === '' || $accountNumber === '' || $accountName === '') {
                $errors[] = 'Please fill in all bank account fields.';
            }

            if (!$errors) {
                $stmt = db()->prepare(
                    'INSERT INTO bank_accounts (user_id, type, bank_name, account_number, account_name, is_default, created_at)
                     VALUES (?, ?, ?, ?, ?, 0, NOW())'
                );
                $stmt->execute([$user['id'], 'bank', $bankName, $accountNumber, $accountName]);
            }
        } else {
            $usdtAddress = clean($_POST['usdt_address'] ?? '');
            $network = clean($_POST['network'] ?? 'TRC20');

            if ($usdtAddress === '') {
                $errors[] = 'Please enter a USDT wallet address.';
            }

            if (!$errors) {
                $stmt = db()->prepare(
                    'INSERT INTO bank_accounts (user_id, type, usdt_address, network, is_default, created_at)
                     VALUES (?, ?, ?, ?, 0, NOW())'
                );
                $stmt->execute([$user['id'], 'usdt', $usdtAddress, $network]);
            }
        }

        if (!$errors) {
            flash('bank_accounts', 'Withdrawal account added.', 'success');
            redirect(rtrim(APP_URL, '/') . '/wallet/bank-accounts.php');
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM bank_accounts WHERE id = ? AND user_id = ?')->execute([$id, $user['id']]);
        flash('bank_accounts', 'Account removed.', 'success');
        redirect(rtrim(APP_URL, '/') . '/wallet/bank-accounts.php');
    } elseif ($action === 'set_default') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('UPDATE bank_accounts SET is_default = 0 WHERE user_id = ?')->execute([$user['id']]);
        db()->prepare('UPDATE bank_accounts SET is_default = 1 WHERE id = ? AND user_id = ?')->execute([$id, $user['id']]);
        flash('bank_accounts', 'Default account updated.', 'success');
        redirect(rtrim(APP_URL, '/') . '/wallet/bank-accounts.php');
    }
}

$stmt = db()->prepare('SELECT * FROM bank_accounts WHERE user_id = ? ORDER BY is_default DESC, created_at DESC');
$stmt->execute([$user['id']]);
$accounts = $stmt->fetchAll();

$pageTitle = 'Withdrawal Accounts';
$activeNav = 'bank-accounts';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<?php if ($errors): ?>
    <div class="alert alert-danger py-2 px-3 small mb-3">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card-surface p-4 mb-4">
            <h5 class="fw-bold mb-3">Add Bank Account</h5>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="type" value="bank">
                <div class="mb-3">
                    <label class="form-label small">Bank Name</label>
                    <input type="text" class="form-control" name="bank_name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Account Number</label>
                    <input type="text" class="form-control" name="account_number" required>
                </div>
                <div class="mb-4">
                    <label class="form-label small">Account Name</label>
                    <input type="text" class="form-control" name="account_name" required>
                </div>
                <button type="submit" class="btn btn-brand w-100">Add Bank Account</button>
            </form>
        </div>

        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Add USDT Wallet</h5>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="type" value="usdt">
                <div class="mb-3">
                    <label class="form-label small">Wallet Address</label>
                    <input type="text" class="form-control" name="usdt_address" required>
                </div>
                <div class="mb-4">
                    <label class="form-label small">Network</label>
                    <select class="form-select" name="network">
                        <option value="TRC20">TRC20 (Tron)</option>
                        <option value="ERC20">ERC20 (Ethereum)</option>
                        <option value="BEP20">BEP20 (BSC)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-brand w-100">Add USDT Wallet</button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Saved Accounts</h5>
            <?php if (!$accounts): ?>
                <div class="text-center py-5" style="color:var(--text-muted);">No withdrawal accounts saved yet.</div>
            <?php else: ?>
                <?php foreach ($accounts as $acc): ?>
                    <div class="d-flex justify-content-between align-items-center p-3 mb-2" style="border:1px solid var(--border);border-radius:var(--radius-sm);">
                        <div>
                            <?php if ($acc['type'] === 'bank'): ?>
                                <div class="fw-semibold"><?= e($acc['bank_name']) ?> <?php if ($acc['is_default']): ?><span class="pill pill-active ms-1">Default</span><?php endif; ?></div>
                                <div class="small" style="color:var(--text-muted);"><?= e($acc['account_number']) ?> — <?= e($acc['account_name']) ?></div>
                            <?php else: ?>
                                <div class="fw-semibold">USDT (<?= e($acc['network']) ?>) <?php if ($acc['is_default']): ?><span class="pill pill-active ms-1">Default</span><?php endif; ?></div>
                                <div class="small" style="color:var(--text-muted);word-break:break-all;"><?= e($acc['usdt_address']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-1">
                            <?php if (!$acc['is_default']): ?>
                                <form method="POST" action="">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="set_default">
                                    <input type="hidden" name="id" value="<?= (int) $acc['id'] ?>">
                                    <button type="submit" class="btn btn-outline-brand btn-sm">Set Default</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" action="" onsubmit="return confirm('Remove this account?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $acc['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
