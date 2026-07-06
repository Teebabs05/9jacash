<div class="container py-5 text-center">
    <h1 class="display-1 fw-bold text-danger">Oops</h1>
    <p class="lead">Something went wrong. Please try again shortly.</p>
    <?php if (config('app.debug') && !empty($message)): ?>
        <pre class="text-start bg-dark text-white p-3 rounded mx-auto" style="max-width:700px;"><?= e($message) ?></pre>
    <?php endif; ?>
    <a href="<?= base_url('/') ?>" class="btn btn-brand rounded-pill px-4 mt-2">Go Home</a>
</div>
