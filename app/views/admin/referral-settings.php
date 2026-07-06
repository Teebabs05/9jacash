<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">Referral Settings</h6>
            <p class="small text-muted-soft">Configure commission percentages and bonuses. Set a level to 0 to disable it.</p>
            <form method="post" action="<?= base_url('admin/referral-settings') ?>">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label small fw-semibold">Signup Bonus (flat, paid to referrer)</label><input type="number" step="0.01" name="referral_signup_bonus" class="form-control" value="<?= e(setting('referral_signup_bonus', 0)) ?>"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Deposit Bonus % (Level 1)</label><input type="number" step="0.01" name="referral_deposit_percent" class="form-control" value="<?= e(setting('referral_deposit_percent', 0)) ?>"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Mining Bonus % (Level 1)</label><input type="number" step="0.01" name="referral_mining_percent" class="form-control" value="<?= e(setting('referral_mining_percent', 0)) ?>"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Task Bonus % (Level 1)</label><input type="number" step="0.01" name="referral_task_percent" class="form-control" value="<?= e(setting('referral_task_percent', 0)) ?>"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Level 2 %</label><input type="number" step="0.01" name="referral_level_2_percent" class="form-control" value="<?= e(setting('referral_level_2_percent', 0)) ?>"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Level 3 %</label><input type="number" step="0.01" name="referral_level_3_percent" class="form-control" value="<?= e(setting('referral_level_3_percent', 0)) ?>"></div>
                <button class="btn btn-brand rounded-pill px-4" type="submit">Save Settings</button>
            </form>
        </div>
    </div>
</div>
