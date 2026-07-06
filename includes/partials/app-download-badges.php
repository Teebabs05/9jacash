<?php
/**
 * Play Store / App Store download badges. Renders nothing if neither
 * URL is configured; each badge is independently optional.
 */
$playstoreUrl = (string) get_setting('playstore_url', '');
$appstoreUrl = (string) get_setting('appstore_url', '');

if ($playstoreUrl !== '' || $appstoreUrl !== ''):
?>
<div class="d-flex gap-2 flex-wrap app-download-badges">
    <?php if ($playstoreUrl !== ''): ?>
        <a href="<?= e($playstoreUrl) ?>" target="_blank" rel="noopener" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-2">
            <i class="bi bi-google-play"></i> Get it on Google Play
        </a>
    <?php endif; ?>
    <?php if ($appstoreUrl !== ''): ?>
        <a href="<?= e($appstoreUrl) ?>" target="_blank" rel="noopener" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-2">
            <i class="bi bi-apple"></i> Download on the App Store
        </a>
    <?php endif; ?>
</div>
<?php endif; ?>
