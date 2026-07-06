<h4 class="fw-bold mb-1">Verify your email</h4>
<p class="text-muted-soft mb-4">We sent a 6-digit code to <strong><?= e($email ?? '') ?></strong>.</p>

<form method="post" action="<?= base_url('verify-email') ?>" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3">
        <input type="text" name="code" class="form-control form-control-lg text-center" style="letter-spacing:8px;" maxlength="6" pattern="\d{6}" required autofocus>
    </div>
    <button class="btn btn-brand w-100 rounded-pill py-2" type="submit">Verify Email</button>
</form>

<form method="post" action="<?= base_url('verify-email/resend') ?>" class="text-center mt-3">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-link small">Resend code</button>
</form>
