<h4 class="fw-bold mb-1">Two-Factor Verification</h4>
<p class="text-muted-soft mb-4">Enter the 6-digit code from your authenticator app.</p>

<form method="post" action="<?= base_url('login/2fa') ?>" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3">
        <input type="text" name="code" class="form-control form-control-lg text-center" style="letter-spacing:8px;" maxlength="6" pattern="\d{6}" required autofocus>
    </div>
    <button class="btn btn-brand w-100 rounded-pill py-2" type="submit">Verify</button>
</form>
