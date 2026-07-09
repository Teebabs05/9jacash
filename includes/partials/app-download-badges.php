<?php
/**
 * Play Store / App Store download badges. Renders nothing if neither
 * URL is configured; each badge is independently optional.
 *
 * Set $downloadBadgesCompact = true before including this partial for
 * a smaller, shorter-label variant (used on the dashboard, where the
 * badges sit above the greeting rather than in a spacious hero).
 */
$playstoreUrl = (string) get_setting('playstore_url', '');
$appstoreUrl = (string) get_setting('appstore_url', '');
$compact = !empty($downloadBadgesCompact);

if ($playstoreUrl !== '' || $appstoreUrl !== ''):
?>
<div class="d-flex gap-2 app-download-badges<?= $compact ? ' compact' : '' ?>">
    <?php if ($playstoreUrl !== ''): ?>
        <a href="<?= e($playstoreUrl) ?>" target="_blank" rel="noopener" class="btn btn-dark btn-sm d-inline-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-google-play"></i> <?= $compact ? 'Google Play' : 'Get it on Google Play' ?>
        </a>
    <?php endif; ?>
    <?php if ($appstoreUrl !== ''): ?>
        <a href="<?= e($appstoreUrl) ?>" target="_blank" rel="noopener" class="btn btn-dark btn-sm d-inline-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-apple"></i> <?= $compact ? 'App Store' : 'Download on the App Store' ?>
        </a>
    <?php endif; ?>
</div>
<?php endif; ?>
