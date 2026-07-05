<h4 class="fw-bold mb-1">Welcome back</h4>
<p class="text-muted-soft mb-4">Login to access your dashboard.</p>

<form method="post" action="<?= base_url('login') ?>" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Username or Email</label>
        <input type="text" name="login" class="form-control" required autofocus>
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="d-flex justify-content-end mb-3">
        <a href="<?= base_url('forgot-password') ?>" class="small">Forgot password?</a>
    </div>
    <button class="btn btn-brand w-100 rounded-pill py-2" type="submit">Login</button>
</form>

<p class="text-center small mt-4 mb-0">Don't have an account? <a href="<?= base_url('register') ?>" class="fw-semibold">Create one</a></p>
