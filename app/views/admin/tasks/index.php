<div class="row g-3">
    <div class="col-lg-4">
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">Create Task</h6>
            <form method="post" action="<?= base_url('admin/tasks') ?>">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label small fw-semibold">Title</label><input type="text" name="title" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Category</label>
                    <select name="category" class="form-select">
                        <?php foreach (['watch_video','visit_website','facebook_like','instagram_follow','tiktok_follow','telegram_join','twitter_follow','whatsapp_join','app_download','daily_login','quiz','survey','referral_task','other'] as $c): ?>
                            <option value="<?= $c ?>"><?= ucfirst(str_replace('_',' ',$c)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label small fw-semibold">Link</label><input type="url" name="link" class="form-control" placeholder="https://"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Reward Amount</label><input type="number" step="0.01" name="reward_amount" class="form-control" required></div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Repeatable</label>
                    <select name="repeatable" class="form-select"><option value="once">Once</option><option value="daily">Daily</option></select>
                </div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="requires_proof" value="1" checked id="reqProof"><label class="form-check-label small" for="reqProof">Requires proof upload &amp; admin approval</label></div>
                <button class="btn btn-brand rounded-pill px-4 w-100" type="submit">Create Task</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="surface-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">All Tasks</h6>
                <a href="<?= base_url('admin/tasks-submissions') ?>" class="small">Review submissions</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Title</th><th>Category</th><th>Reward</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($tasks as $t): ?>
                        <tr>
                            <td><?= e($t['title']) ?></td>
                            <td class="text-capitalize small"><?= e(str_replace('_',' ',$t['category'])) ?></td>
                            <td><?= money($t['reward_amount']) ?></td>
                            <td><span class="badge badge-status-<?= $t['status'] === 'active' ? 'active' : 'suspended' ?> text-capitalize"><?= e($t['status']) ?></span></td>
                            <td class="d-flex gap-1">
                                <form method="post" action="<?= base_url('admin/tasks/' . $t['id'] . '/update') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="title" value="<?= e($t['title']) ?>">
                                    <input type="hidden" name="description" value="<?= e($t['description']) ?>">
                                    <input type="hidden" name="reward_amount" value="<?= e($t['reward_amount']) ?>">
                                    <input type="hidden" name="status" value="<?= $t['status'] === 'active' ? 'inactive' : 'active' ?>">
                                    <button class="btn btn-sm btn-outline-secondary"><?= $t['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                                </form>
                                <form method="post" action="<?= base_url('admin/tasks/' . $t['id'] . '/delete') ?>" onsubmit="return confirm('Delete this task?')">
                                    <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
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
