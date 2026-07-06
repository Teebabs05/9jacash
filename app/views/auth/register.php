<h4 class="fw-bold mb-1">Create your account</h4>
<p class="text-muted-soft mb-4">Start earning with mining, tasks &amp; referrals today.</p>

<form method="post" action="<?= base_url('register') ?>" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label small fw-semibold">Full Name</label>
            <input type="text" name="full_name" class="form-control" value="<?= old('full_name') ?>" required minlength="3">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Username</label>
            <input type="text" name="username" class="form-control" value="<?= old('username') ?>" required pattern="[a-z0-9_]{4,20}" title="4-20 chars: letters, numbers, underscore">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Phone</label>
            <input type="tel" name="phone" class="form-control" value="<?= old('phone') ?>" required>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Email Address</label>
            <input type="email" name="email" class="form-control" value="<?= old('email') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Country</label>
            <input type="text" name="country" class="form-control" value="<?= old('country') ?: 'Nigeria' ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">State</label>
            <input type="text" name="state" class="form-control" value="<?= old('state') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Password</label>
            <input type="password" name="password" class="form-control" required minlength="8">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" required minlength="8">
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Referral Code <span class="text-muted-soft fw-normal">(optional)</span></label>
            <input type="text" name="referral_code" class="form-control" value="<?= old('referral_code') ?: e($refCode ?? '') ?>">
        </div>
        <?php if (setting('recaptcha_enabled') === '1' && config('recaptcha.site_key')): ?>
        <div class="col-12">
            <div class="g-recaptcha" data-sitekey="<?= e(config('recaptcha.site_key')) ?>"></div>
        </div>
        <?php endif; ?>
        <div class="col-12 form-check">
            <input class="form-check-input" type="checkbox" required id="terms">
            <label class="form-check-label small" for="terms">I agree to the <a href="<?= base_url('terms') ?>" target="_blank">Terms</a> &amp; <a href="<?= base_url('privacy') ?>" target="_blank">Privacy Policy</a>.</label>
        </div>
    </div>
    <button class="btn btn-brand w-100 rounded-pill py-2 mt-4" type="submit">Create Account</button>
</form>

<?php if (setting('recaptcha_enabled') === '1' && config('recaptcha.site_key')): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>

<p class="text-center small mt-4 mb-0">Already have an account? <a href="<?= base_url('login') ?>" class="fw-semibold">Login</a></p>
