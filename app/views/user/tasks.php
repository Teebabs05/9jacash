<?php
$icons = [
    'watch_video' => 'fa-play', 'visit_website' => 'fa-globe', 'facebook_like' => 'fa-facebook fa-brands',
    'instagram_follow' => 'fa-instagram fa-brands', 'tiktok_follow' => 'fa-tiktok fa-brands', 'telegram_join' => 'fa-telegram fa-brands',
    'twitter_follow' => 'fa-x-twitter fa-brands', 'whatsapp_join' => 'fa-whatsapp fa-brands', 'app_download' => 'fa-mobile-screen',
    'daily_login' => 'fa-calendar-check', 'quiz' => 'fa-circle-question', 'survey' => 'fa-list-check', 'referral_task' => 'fa-share-nodes', 'other' => 'fa-star',
];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Available Tasks</h5>
    <a href="<?= base_url('tasks/history') ?>" class="small">View submission history</a>
</div>
<div class="row g-3">
    <?php foreach ($tasks as $task): ?>
    <div class="col-md-6 col-xl-4">
        <div class="surface-card p-4 h-100 d-flex flex-column">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="icon-badge"><i class="fa-solid <?= $icons[$task['category']] ?? 'fa-star' ?>"></i></span>
                <span class="badge bg-success-subtle text-success-emphasis ms-auto"><?= money($task['reward_amount']) ?></span>
            </div>
            <h6 class="fw-bold"><?= e($task['title']) ?></h6>
            <p class="small text-muted-soft flex-grow-1"><?= e($task['description']) ?></p>
            <?php if ($task['link']): ?><a href="<?= e($task['link']) ?>" target="_blank" class="btn btn-outline-brand btn-sm rounded-pill mb-2"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Open Link</a><?php endif; ?>
            <button class="btn btn-brand rounded-pill" data-bs-toggle="modal" data-bs-target="#taskModal<?= $task['id'] ?>">Complete Task</button>
        </div>
    </div>

    <div class="modal fade" id="taskModal<?= $task['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="<?= base_url('tasks/' . $task['id'] . '/submit') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="modal-header"><h6 class="modal-title">Complete: <?= e($task['title']) ?></h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <?php if ($task['requires_proof']): ?>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Upload Proof (screenshot)</label>
                                <input type="file" name="proof" class="form-control" accept="image/*" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Notes <span class="text-muted-soft fw-normal">(optional)</span></label>
                                <textarea name="proof_text" class="form-control" rows="2"></textarea>
                            </div>
                        <?php else: ?>
                            <p class="small text-muted-soft">Click submit to instantly claim your reward for this task.</p>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer"><button class="btn btn-brand rounded-pill px-4">Submit</button></div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($tasks)): ?><p class="text-muted-soft">No tasks available right now. Check back later!</p><?php endif; ?>
</div>
