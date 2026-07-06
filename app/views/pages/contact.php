<div class="container py-5">
    <div class="row justify-content-center g-4">
        <div class="col-lg-5">
            <h1 class="fw-bold mb-3">Get in Touch</h1>
            <p class="text-muted-soft">Have a question or need help with your account? Reach out and our team will respond shortly.</p>
            <ul class="list-unstyled text-muted-soft">
                <li class="mb-2"><i class="fa-solid fa-envelope me-2" style="color:var(--c-primary);"></i><?= e(setting('contact_email', 'support@9jacash.com')) ?></li>
                <li class="mb-2"><i class="fa-solid fa-phone me-2" style="color:var(--c-primary);"></i><?= e(setting('contact_phone', '')) ?></li>
            </ul>
            <?php if (is_logged_in()): ?>
            <a href="<?= base_url('support/new') ?>" class="btn btn-outline-brand rounded-pill px-4">Open a Support Ticket</a>
            <?php endif; ?>
        </div>
        <div class="col-lg-6">
            <div class="surface-card p-4">
                <form method="post" action="<?= base_url('contact') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3"><label class="form-label small fw-semibold">Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label small fw-semibold">Email</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label small fw-semibold">Message</label><textarea name="message" class="form-control" rows="5" required></textarea></div>
                    <button class="btn btn-brand rounded-pill px-4" type="submit">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>
