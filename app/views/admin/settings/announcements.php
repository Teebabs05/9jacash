<div class="row g-3">
    <div class="col-lg-5">
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">Publish Announcement</h6>
            <p class="small text-muted-soft">This replaces the site-wide banner shown to all logged-in users.</p>
            <form method="post" action="<?= base_url('admin/announcements') ?>">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label small">Title</label><input type="text" name="title" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small">Message</label><textarea name="message" class="form-control" rows="3" required></textarea></div>
                <button class="btn btn-brand rounded-pill px-4 w-100">Publish</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">History</h6>
            <?php foreach ($announcements as $a): ?>
                <div class="border-bottom py-2" style="border-color:var(--border-color) !important;">
                    <div class="fw-semibold"><?= e($a['title']) ?></div>
                    <div class="small text-muted-soft"><?= e($a['message']) ?></div>
                    <div class="small text-muted-soft"><?= time_ago($a['created_at']) ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($announcements)): ?><p class="text-muted-soft small text-center py-3">No announcements yet.</p><?php endif; ?>
        </div>
    </div>
</div>
