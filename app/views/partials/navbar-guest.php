<nav class="navbar navbar-expand-lg site-navbar sticky-top py-3">
    <div class="container">
        <a class="navbar-brand" href="<?= base_url('/') ?>"><?php require APP_PATH . '/views/partials/logo.php'; ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#guestNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="guestNav">
            <ul class="navbar-nav mx-auto gap-1">
                <li class="nav-item"><a class="nav-link" href="<?= base_url('/') ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('pricing') ?>">Mining Plans</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('referral-info') ?>">Referrals</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('faq') ?>">FAQ</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('about') ?>">About</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('contact') ?>">Contact</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <button class="theme-toggle-btn" type="button" title="Toggle theme">
                    <i class="theme-toggle-icon fa-solid fa-moon"></i>
                </button>
                <?php if (is_logged_in()): ?>
                    <a href="<?= base_url(is_admin() ? 'admin' : 'dashboard') ?>" class="btn btn-brand rounded-pill px-3">Dashboard</a>
                <?php else: ?>
                    <a href="<?= base_url('login') ?>" class="btn btn-outline-brand rounded-pill px-3">Login</a>
                    <a href="<?= base_url('register') ?>" class="btn btn-accent rounded-pill px-3">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
