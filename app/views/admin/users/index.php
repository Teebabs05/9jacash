<div class="surface-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-bold mb-0">Manage Users</h6>
        <div class="d-flex gap-2">
            <form class="d-flex gap-2" method="get">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search users..." value="<?= e($search) ?>">
                <button class="btn btn-sm btn-outline-brand">Search</button>
            </form>
            <a href="<?= base_url('admin/users/export') ?>" class="btn btn-outline-brand btn-sm rounded-pill"><i class="fa-solid fa-download me-1"></i>Export</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>User</th><th>Email</th><th>Phone</th><th>Status</th><th>Joined</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $u): ?>
                <tr>
                    <td><?= e($u['full_name']) ?><div class="small text-muted-soft">@<?= e($u['username']) ?></div></td>
                    <td class="small"><?= e($u['email']) ?></td>
                    <td class="small"><?= e($u['phone']) ?></td>
                    <td><span class="badge badge-status-<?= e($u['status']) ?> text-capitalize"><?= e($u['status']) ?></span></td>
                    <td class="small text-muted-soft"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td><a href="<?= base_url('admin/users/' . $u['id']) ?>" class="btn btn-sm btn-outline-brand">Manage</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?><tr><td colspan="6" class="text-center text-muted-soft py-4">No users found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $baseUrl = base_url('admin/users') . ($search ? '?q=' . urlencode($search) : ''); require APP_PATH . '/views/partials/pagination.php'; ?>
</div>
