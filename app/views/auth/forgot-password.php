<h4 class="fw-bold mb-1">Forgot password?</h4>
<p class="text-muted-soft mb-4">Enter your email and we'll send you a reset link.</p>

<form method="post" action="<?= base_url('forgot-password') ?>" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Email Address</label>
        <input type="email" name="email" class="form-control" required autofocus>
    </div>
    <button class="btn btn-brand w-100 rounded-pill py-2" type="submit">Send Reset Link</button>
</form>

<p class="text-center small mt-4 mb-0"><a href="<?= base_url('login') ?>">Back to login</a></p>
