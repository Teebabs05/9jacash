<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$admin = current_admin();

if (($admin['role'] ?? '') !== 'super_admin') {
    flash('admin', 'Only super administrators can manage staff accounts.', 'error');
    redirect(rtrim(APP_URL, '/') . '/admin/index.php');
}

$staffAssignableRoles = ['admin', 'moderator'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'add_staff') {
        $fullName = clean($_POST['full_name'] ?? '');
        $username = strtolower(clean($_POST['username'] ?? ''));
        $email = strtolower(clean($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = $_POST['role'] ?? 'admin';

        if (strlen($fullName) < 3) {
            $errors[] = 'Please enter a full name.';
        }
        if (!preg_match('/^[a-z0-9_.]{3,50}$/', $username)) {
            $errors[] = 'Username must be 3-50 characters (letters, numbers, dot, underscore only).';
        }
        if (!is_valid_email($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (!is_strong_password($password)) {
            $errors[] = 'Password must be at least 8 characters and include letters and numbers.';
        }
        if (!in_array($role, $staffAssignableRoles, true)) {
            $errors[] = 'Invalid role selected.';
        }

        if (!$errors) {
            $stmt = db()->prepare('SELECT id FROM admins WHERE username = ? OR email = ? LIMIT 1');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = 'An admin with that username or email already exists.';
            }
        }

        if (!$errors) {
            $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            db()->prepare(
                'INSERT INTO admins (username, email, password, full_name, role, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
            )->execute([$username, $email, $hashed, $fullName, $role, 'active']);

            log_activity(null, (int) $admin['id'], 'staff_added', "Added staff member {$username} ({$role})");
            flash('admin', 'Staff member added successfully.', 'success');
            redirect(rtrim(APP_URL, '/') . '/admin/staff.php');
        }
    } elseif ($action === 'update_role') {
        $staffId = (int) ($_POST['staff_id'] ?? 0);
        $role = $_POST['role'] ?? '';

        if (!in_array($role, $staffAssignableRoles, true)) {
            $errors[] = 'Invalid role selected.';
        } elseif ($staffId === (int) $admin['id']) {
            $errors[] = 'You cannot change your own role here.';
        } else {
            $stmt = db()->prepare("SELECT role FROM admins WHERE id = ? LIMIT 1");
            $stmt->execute([$staffId]);
            $target = $stmt->fetch();

            if (!$target || $target['role'] === 'super_admin') {
                $errors[] = 'That staff account cannot be modified here.';
            } else {
                db()->prepare('UPDATE admins SET role = ?, updated_at = NOW() WHERE id = ?')->execute([$role, $staffId]);
                log_activity(null, (int) $admin['id'], 'staff_role_changed', "Changed staff #{$staffId} role to {$role}");
                flash('admin', 'Staff role updated.', 'success');
            }
        }

        if (!$errors) {
            redirect(rtrim(APP_URL, '/') . '/admin/staff.php');
        }
    } elseif ($action === 'toggle_status') {
        $staffId = (int) ($_POST['staff_id'] ?? 0);

        if ($staffId === (int) $admin['id']) {
            $errors[] = 'You cannot disable your own account.';
        } else {
            $stmt = db()->prepare('SELECT role, status FROM admins WHERE id = ? LIMIT 1');
            $stmt->execute([$staffId]);
            $target = $stmt->fetch();

            if (!$target || $target['role'] === 'super_admin') {
                $errors[] = 'That staff account cannot be modified here.';
            } else {
                $newStatus = $target['status'] === 'active' ? 'disabled' : 'active';
                db()->prepare('UPDATE admins SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$newStatus, $staffId]);
                log_activity(null, (int) $admin['id'], 'staff_status_changed', "Set staff #{$staffId} status to {$newStatus}");
                flash('admin', 'Staff status updated.', 'success');
            }
        }

        if (!$errors) {
            redirect(rtrim(APP_URL, '/') . '/admin/staff.php');
        }
    } elseif ($action === 'remove_staff') {
        $staffId = (int) ($_POST['staff_id'] ?? 0);

        if ($staffId === (int) $admin['id']) {
            $errors[] = 'You cannot remove your own account.';
        } else {
            $stmt = db()->prepare('SELECT username, role FROM admins WHERE id = ? LIMIT 1');
            $stmt->execute([$staffId]);
            $target = $stmt->fetch();

            if (!$target || $target['role'] === 'super_admin') {
                $errors[] = 'That staff account cannot be removed here.';
            } else {
                db()->prepare('DELETE FROM admins WHERE id = ?')->execute([$staffId]);
                log_activity(null, (int) $admin['id'], 'staff_removed', "Removed staff member {$target['username']}");
                flash('admin', 'Staff member removed.', 'success');
            }
        }

        if (!$errors) {
            redirect(rtrim(APP_URL, '/') . '/admin/staff.php');
        }
    }
}

$staff = db()->query('SELECT * FROM admins ORDER BY FIELD(role, \'super_admin\',\'admin\',\'moderator\',\'support\'), created_at ASC')->fetchAll();

$pageTitle = 'Staff Management';
$activeNav = 'staff';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<?php if ($errors): ?>
    <div class="alert alert-danger py-2 px-3 small mb-3">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Add Staff Member</h5>
            <form method="POST" action="" data-loading-submit>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_staff">
                <div class="mb-3">
                    <label class="form-label small">Full Name</label>
                    <input type="text" class="form-control" name="full_name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Username</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Password</label>
                    <input type="password" class="form-control" name="password" required minlength="8">
                </div>
                <div class="mb-4">
                    <label class="form-label small">Role</label>
                    <select class="form-select" name="role">
                        <option value="admin">Admin</option>
                        <option value="moderator">Moderator</option>
                    </select>
                    <div class="form-text">Admins have full panel access. Moderators are intended for lighter, day-to-day tasks (no further restrictions are enforced beyond this label yet).</div>
                </div>
                <button type="submit" class="btn btn-brand w-100">Add Staff Member</button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Staff Accounts</h5>
            <div class="table-responsive">
                <table class="table ledger-table mb-0">
                    <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Last Login</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($staff as $s): ?>
                            <tr>
                                <td><?= e($s['full_name']) ?><div class="small" style="color:var(--text-muted);">@<?= e($s['username']) ?></div></td>
                                <td>
                                    <?php if ($s['role'] === 'super_admin'): ?>
                                        <span class="pill pill-active">Super Admin</span>
                                    <?php else: ?>
                                        <form method="POST" action="" class="d-inline-flex align-items-center gap-1">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="update_role">
                                            <input type="hidden" name="staff_id" value="<?= (int) $s['id'] ?>">
                                            <select class="form-select form-select-sm" name="role" onchange="this.form.submit()" style="width:auto;">
                                                <option value="admin" <?= $s['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                <option value="moderator" <?= $s['role'] === 'moderator' ? 'selected' : '' ?>>Moderator</option>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td><span class="pill pill-<?= $s['status'] === 'active' ? 'active' : 'rejected' ?>"><?= e(ucfirst($s['status'])) ?></span></td>
                                <td class="small" style="color:var(--text-muted);"><?= $s['last_login_at'] ? e(time_ago($s['last_login_at'])) : 'Never' ?></td>
                                <td class="text-end">
                                    <?php if ($s['role'] !== 'super_admin' && (int) $s['id'] !== (int) $admin['id']): ?>
                                        <form method="POST" action="" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="staff_id" value="<?= (int) $s['id'] ?>">
                                            <button type="submit" class="btn btn-outline-brand btn-sm"><?= $s['status'] === 'active' ? 'Disable' : 'Enable' ?></button>
                                        </form>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Permanently remove this staff member?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="remove_staff">
                                            <input type="hidden" name="staff_id" value="<?= (int) $s['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                                        </form>
                                    <?php endif; ?>
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
