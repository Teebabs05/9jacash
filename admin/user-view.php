<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$admin = current_admin();
$userId = (int) ($_GET['id'] ?? 0);
$errors = [];

$stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    flash('users', 'User not found.', 'error');
    redirect(rtrim(APP_URL, '/') . '/admin/users.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'update_info') {
        $fullName = clean($_POST['full_name'] ?? '');
        $email = strtolower(clean($_POST['email'] ?? ''));
        $phone = clean($_POST['phone'] ?? '');
        $kycStatus = $_POST['kyc_status'] ?? $user['kyc_status'];

        if (strlen($fullName) < 3 || !is_valid_email($email)) {
            $errors[] = 'Please provide a valid name and email address.';
        }

        if (!$errors) {
            db()->prepare('UPDATE users SET full_name = ?, email = ?, phone = ?, kyc_status = ?, updated_at = NOW() WHERE id = ?')
                ->execute([$fullName, $email, $phone, $kycStatus, $userId]);
            log_activity($userId, (int) $admin['id'], 'admin_user_updated', 'Admin updated user profile/KYC status');
            flash('users', 'User updated successfully.', 'success');
            redirect(rtrim(APP_URL, '/') . '/admin/user-view.php?id=' . $userId);
        }
    } elseif ($action === 'change_status') {
        $status = $_POST['status'] ?? '';
        if (in_array($status, [USER_STATUS_ACTIVE, USER_STATUS_SUSPENDED, USER_STATUS_BANNED], true)) {
            db()->prepare('UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$status, $userId]);
            notify_user($userId, 'Account Status Updated', 'Your account status was changed to ' . ucfirst($status) . ' by an administrator.', NOTIFY_TYPE_SYSTEM);
            log_activity($userId, (int) $admin['id'], 'admin_status_changed', "Status changed to {$status}");
            flash('users', 'Account status updated.', 'success');
        }
        redirect(rtrim(APP_URL, '/') . '/admin/user-view.php?id=' . $userId);
    } elseif ($action === 'adjust_wallet') {
        $walletType = $_POST['wallet_type'] ?? WALLET_MAIN;
        $direction = $_POST['direction'] ?? 'credit';
        $amount = (float) ($_POST['amount'] ?? 0);
        $reason = clean($_POST['reason'] ?? '');

        if (!in_array($walletType, WALLET_TYPES, true)) {
            $errors[] = 'Invalid wallet type.';
        } elseif ($amount <= 0) {
            $errors[] = 'Amount must be greater than zero.';
        } elseif ($reason === '') {
            $errors[] = 'Please provide a reason for this adjustment (visible in the audit log).';
        }

        if (!$errors) {
            try {
                if ($direction === 'debit') {
                    wallet_debit($userId, $walletType, $amount, LEDGER_SOURCE_ADMIN_ADJUSTMENT, "Admin adjustment: {$reason}");
                } else {
                    wallet_credit($userId, $walletType, $amount, LEDGER_SOURCE_ADMIN_ADJUSTMENT, "Admin adjustment: {$reason}");
                }
                notify_user($userId, 'Wallet Adjusted', ucfirst($direction) . ' of ' . money($amount) . " applied to your {$walletType} wallet by an administrator.", NOTIFY_TYPE_SYSTEM);
                log_activity($userId, (int) $admin['id'], 'admin_wallet_adjustment', "{$direction} " . money($amount) . " ({$walletType}): {$reason}");
                flash('users', 'Wallet adjusted successfully.', 'success');
                redirect(rtrim(APP_URL, '/') . '/admin/user-view.php?id=' . $userId);
            } catch (Throwable $e) {
                $errors[] = $e->getMessage() === 'Insufficient wallet balance.' ? 'Insufficient balance in that wallet for this debit.' : 'Could not process this adjustment.';
            }
        }
    } elseif ($action === 'update_payout_schedule') {
        $schedule = $_POST['payout_schedule'] ?? PAYOUT_SCHEDULE_DEFAULT;
        if ($schedule !== PAYOUT_SCHEDULE_DEFAULT && !in_array($schedule, PAYOUT_SCHEDULES, true)) {
            $schedule = PAYOUT_SCHEDULE_DEFAULT;
        }
        db()->prepare('UPDATE users SET payout_schedule = ? WHERE id = ?')->execute([$schedule, $userId]);
        log_activity($userId, (int) $admin['id'], 'admin_payout_schedule_changed', "Mining payout schedule set to {$schedule}");
        flash('users', 'Mining payout schedule updated.', 'success');
        redirect(rtrim(APP_URL, '/') . '/admin/user-view.php?id=' . $userId);
    } elseif ($action === 'send_reset') {
        Auth::sendPasswordReset($user['email']);
        log_activity($userId, (int) $admin['id'], 'admin_password_reset_sent', 'Admin triggered a password reset email');
        flash('users', 'Password reset email sent to the user.', 'success');
        redirect(rtrim(APP_URL, '/') . '/admin/user-view.php?id=' . $userId);
    } elseif ($action === 'login_as') {
        $_SESSION['impersonating_admin_id'] = (int) $admin['id'];
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $user['username'];
        log_activity($userId, (int) $admin['id'], 'admin_login_as', 'Admin logged in as this user');
        redirect(rtrim(APP_URL, '/') . '/user/dashboard.php');
    } elseif ($action === 'delete') {
        log_activity(null, (int) $admin['id'], 'admin_user_deleted', "Deleted user #{$userId} ({$user['username']})");
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
        flash('users', 'User deleted permanently.', 'success');
        redirect(rtrim(APP_URL, '/') . '/admin/users.php');
    }
}

$wallet = get_wallet($userId);

$stmt = db()->prepare('SELECT * FROM wallet_ledger WHERE user_id = ? ORDER BY created_at DESC LIMIT 15');
$stmt->execute([$userId]);
$ledger = $stmt->fetchAll();

$stmt = db()->prepare('SELECT COUNT(*) AS c FROM referrals WHERE user_id = ? AND level = 1');
$stmt->execute([$userId]);
$directReferrals = (int) $stmt->fetch()['c'];

$pageTitle = 'Manage ' . $user['full_name'];
$activeNav = 'users';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<?php if ($errors): ?>
    <div class="alert alert-danger py-2 px-3 small mb-3">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card-surface p-4 mb-4">
            <h5 class="fw-bold mb-3">Profile</h5>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_info">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small">Username</label>
                        <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Referral Code</label>
                        <input type="text" class="form-control" value="<?= e($user['referral_code']) ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Full Name</label>
                        <input type="text" class="form-control" name="full_name" value="<?= e($user['full_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Email</label>
                        <input type="email" class="form-control" name="email" value="<?= e($user['email']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?= e($user['phone']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">KYC Status</label>
                        <select class="form-select" name="kyc_status">
                            <?php foreach (['unverified', 'pending', 'approved', 'rejected'] as $k): ?>
                                <option value="<?= e($k) ?>" <?= $user['kyc_status'] === $k ? 'selected' : '' ?>><?= e(ucfirst($k)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-brand mt-3">Save Changes</button>
            </form>
        </div>

        <div class="card-surface p-4 mb-4">
            <h5 class="fw-bold mb-3">Adjust Wallet</h5>
            <form method="POST" action="" class="row g-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="adjust_wallet">
                <div class="col-md-3">
                    <label class="form-label small">Wallet</label>
                    <select class="form-select" name="wallet_type">
                        <?php foreach (WALLET_TYPES as $wt): ?>
                            <option value="<?= e($wt) ?>"><?= e(ucfirst($wt)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Action</label>
                    <select class="form-select" name="direction">
                        <option value="credit">Credit (Add)</option>
                        <option value="debit">Debit (Remove)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Amount (₦)</label>
                    <input type="number" step="0.01" min="0.01" class="form-control" name="amount" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-brand w-100">Apply</button>
                </div>
                <div class="col-12">
                    <label class="form-label small">Reason (required, logged)</label>
                    <input type="text" class="form-control" name="reason" required>
                </div>
            </form>
        </div>

        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Recent Wallet Activity</h5>
            <?php if (!$ledger): ?>
                <div class="text-center py-4" style="color:var(--text-muted);">No wallet activity yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ledger-table mb-0">
                        <tbody>
                            <?php foreach ($ledger as $l): ?>
                                <tr>
                                    <td><?= e($l['description']) ?></td>
                                    <td class="text-capitalize small"><?= e($l['wallet_type']) ?></td>
                                    <td class="fw-semibold <?= $l['type'] === 'credit' ? 'text-success' : 'text-danger' ?>"><?= $l['type'] === 'credit' ? '+' : '-' ?><?= e(money($l['amount'])) ?></td>
                                    <td class="small text-end" style="color:var(--text-muted);"><?= e(time_ago($l['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card-surface p-4 mb-4">
            <h5 class="fw-bold mb-3">Wallet Balances</h5>
            <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);"><span style="color:var(--text-muted);">Main</span><strong><?= e(money($wallet['main_balance'])) ?></strong></div>
            <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);"><span style="color:var(--text-muted);">Bonus</span><strong><?= e(money($wallet['bonus_balance'])) ?></strong></div>
            <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);"><span style="color:var(--text-muted);">Referral</span><strong><?= e(money($wallet['referral_balance'])) ?></strong></div>
            <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);"><span style="color:var(--text-muted);">Mining</span><strong><?= e(money($wallet['mining_balance'])) ?></strong></div>
            <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);"><span style="color:var(--text-muted);">Pending (locked mining earnings)</span><strong><?= e(money($wallet['pending_balance'])) ?></strong></div>
            <div class="d-flex justify-content-between py-2"><span style="color:var(--text-muted);">Direct Referrals</span><strong><?= number_format($directReferrals) ?></strong></div>
        </div>

        <div class="card-surface p-4 mb-4">
            <h5 class="fw-bold mb-3">Mining Payout Schedule</h5>
            <p class="small mb-2" style="color:var(--text-muted);">Overrides the site-wide default for this user only.</p>
            <form method="POST" action="" class="d-flex gap-2 flex-wrap">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_payout_schedule">
                <select class="form-select" name="payout_schedule" style="max-width:220px;">
                    <option value="default" <?= $user['payout_schedule'] === 'default' ? 'selected' : '' ?>>Site Default (<?= e(PAYOUT_SCHEDULE_LABELS[mining_effective_payout_schedule('default')]) ?>)</option>
                    <?php foreach (PAYOUT_SCHEDULE_LABELS as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $user['payout_schedule'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-outline-brand btn-sm">Save</button>
            </form>
        </div>

        <div class="card-surface p-4 mb-4">
            <h5 class="fw-bold mb-3">Account Status</h5>
            <p class="small" style="color:var(--text-muted);">Current status: <span class="pill pill-<?= $user['status'] === 'active' ? 'active' : 'rejected' ?>"><?= e(ucfirst($user['status'])) ?></span></p>
            <form method="POST" action="" class="d-flex gap-2 flex-wrap">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_status">
                <?php foreach (['active' => 'btn-brand', 'suspended' => 'btn-outline-brand', 'banned' => 'btn-outline-danger'] as $status => $btnClass): ?>
                    <button type="submit" name="status" value="<?= e($status) ?>" class="btn btn-sm <?= $btnClass ?>" <?= $user['status'] === $status ? 'disabled' : '' ?>><?= e(ucfirst($status)) ?></button>
                <?php endforeach; ?>
            </form>
        </div>

        <div class="card-surface p-4 mb-4">
            <h5 class="fw-bold mb-3">Account Actions</h5>
            <form method="POST" action="" class="mb-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="send_reset">
                <button type="submit" class="btn btn-outline-brand w-100">Send Password Reset Email</button>
            </form>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="login_as">
                <button type="submit" class="btn btn-outline-brand w-100">Login As This User</button>
            </form>
        </div>

        <div class="card-surface p-4" style="border-color:var(--danger);">
            <h5 class="fw-bold mb-2 text-danger">Danger Zone</h5>
            <p class="small" style="color:var(--text-muted);">Permanently deletes this user and all associated data (wallet, history, submissions). This cannot be undone.</p>
            <form method="POST" action="" onsubmit="return confirm('Permanently delete this user and ALL their data? This cannot be undone.');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-outline-danger w-100">Delete User Permanently</button>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
