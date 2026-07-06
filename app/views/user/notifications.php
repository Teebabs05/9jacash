<div class="surface-card p-4">
    <h6 class="fw-bold mb-3">Notifications</h6>
    <?php
    $icons = ['success' => 'fa-circle-check text-success', 'error' => 'fa-circle-xmark text-danger', 'info' => 'fa-circle-info text-primary', 'warning' => 'fa-triangle-exclamation text-warning'];
    ?>
    <?php foreach ($notifications as $n): ?>
        <div class="d-flex gap-3 border-bottom py-3" style="border-color:var(--border-color) !important;">
            <i class="fa-solid <?= $icons[$n['type']] ?? 'fa-bell' ?> fs-5 mt-1"></i>
            <div>
                <div class="fw-semibold"><?= e($n['title']) ?></div>
                <div class="small text-muted-soft"><?= e($n['message']) ?></div>
                <div class="small text-muted-soft mt-1"><?= time_ago($n['created_at']) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($notifications)): ?>
        <p class="text-center text-muted-soft py-4">No notifications yet.</p>
    <?php endif; ?>
</div>
