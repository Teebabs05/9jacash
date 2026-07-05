<?php
$base = rtrim(APP_URL, '/');
$loggedIn = Auth::isLoggedIn();
?>
<nav class="navbar navbar-expand-lg site-nav py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= e($base) ?>/index.php" style="color:var(--text);">
            <span class="brand-mark">9</span>
            <span>9JACASH</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="siteNavbar">
            <ul class="navbar-nav mx-auto gap-lg-2 mt-3 mt-lg-0">
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                <li class="nav-item"><a class="nav-link" href="#mining-plans">Mining Plans</a></li>
                <li class="nav-item"><a class="nav-link" href="#referral">Referral Program</a></li>
                <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <button type="button" class="theme-toggle-btn" style="color:var(--text);border-color:var(--border);" data-theme-toggle data-theme-icon><i class="bi bi-moon-stars"></i></button>
                <?php if ($loggedIn): ?>
                    <a href="<?= e($base) ?>/user/dashboard.php" class="btn btn-brand btn-sm">Dashboard</a>
                <?php else: ?>
                    <a href="<?= e($base) ?>/user/login.php" class="btn btn-outline-brand btn-sm">Log In</a>
                    <a href="<?= e($base) ?>/user/register.php" class="btn btn-brand btn-sm">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
