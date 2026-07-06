<?php
$flashSuccess = flash('success');
$flashError = flash('error');
$flashInfo = flash('info');
?>
<?php if ($flashSuccess): ?><div class="d-none" data-flash="success"><?= e($flashSuccess) ?></div><?php endif; ?>
<?php if ($flashError): ?><div class="d-none" data-flash="error"><?= e($flashError) ?></div><?php endif; ?>
<?php if ($flashInfo): ?><div class="d-none" data-flash="info"><?= e($flashInfo) ?></div><?php endif; ?>
