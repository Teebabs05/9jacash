<?php if (setting('announcement_active') === '1' && setting('announcement_text')): ?>
<div class="alert alert-warning rounded-0 mb-0 py-2 text-center small">
    <i class="fa-solid fa-bullhorn me-1"></i> <?= e(setting('announcement_text')) ?>
</div>
<?php endif; ?>
