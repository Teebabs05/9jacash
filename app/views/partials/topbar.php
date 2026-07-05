<?php
$u = current_user();
$unread = is_logged_in() ? \App\Models\Notification::unreadCount((int) $u['id']) : 0;
?>
<header class="app-topbar px-3 py-2 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-light border d-lg-none" data-sidebar-toggle><i class="fa-solid fa-bars"></i></button>
        <h5 class="mb-0 fw-bold d-none d-sm-block"><?= e($title ?? '') ?></h5>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button class="theme-toggle-btn" type="button" title="Toggle theme"><i class="theme-toggle-icon fa-solid fa-moon"></i></button>
        <a href="<?= base_url('notifications') ?>" class="theme-toggle-btn position-relative" title="Notifications">
            <i class="fa-solid fa-bell"></i>
            <?php if ($unread > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem;"><?= $unread ?></span><?php endif; ?>
        </a>
        <div class="dropdown">
            <button class="btn btn-light border d-flex align-items-center gap-2 rounded-pill px-2 py-1" data-bs-toggle="dropdown">
                <img src="<?= e($u ? user_avatar_url($u) : '') ?>" class="rounded-circle" width="30" height="30" style="object-fit:cover;">
                <span class="d-none d-md-inline small fw-semibold"><?= e($u['username'] ?? '') ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= base_url('profile') ?>"><i class="fa-solid fa-user me-2"></i>Profile</a></li>
                <?php if (is_admin()): ?>
                <li><a class="dropdown-item" href="<?= base_url('dashboard') ?>"><i class="fa-solid fa-user-circle me-2"></i>User View</a></li>
                <?php else: ?>
                <li><a class="dropdown-item" href="<?= base_url('support') ?>"><i class="fa-solid fa-headset me-2"></i>Support</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="<?= base_url('logout') ?>" method="post">
                        <?= csrf_field() ?>
                        <button class="dropdown-item text-danger" type="submit"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
