<div class="row g-3">
    <div class="col-lg-4">
        <div class="surface-card p-4 text-center">
            <h6 class="fw-bold mb-3">Your Referral QR Code</h6>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($referralLink) ?>" alt="Referral QR Code" class="rounded-3 mb-3" width="180" height="180" loading="lazy">
            <p class="small text-muted-soft">Share this code or the link below to earn referral bonuses.</p>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="surface-card p-4 mb-3">
            <h6 class="fw-bold mb-3">Your Referral Link</h6>
            <div class="input-group mb-3">
                <input type="text" class="form-control" id="refLink" value="<?= e($referralLink) ?>" readonly>
                <button class="btn btn-outline-brand" type="button" onclick="navigator.clipboard.writeText(document.getElementById('refLink').value); NineJC.toast('success','Link copied!')">Copy</button>
            </div>
            <div class="input-group">
                <span class="input-group-text">Referral Code</span>
                <input type="text" class="form-control fw-bold" value="<?= e($referralCode) ?>" readonly>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="stat-tile"><div class="value"><?= count($downline) ?></div><div class="label">Total Referrals</div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-tile"><div class="value"><?= money($totalEarned) ?></div><div class="label">Total Earned</div></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-6">
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">Your Downline</h6>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>User</th><th>Joined</th></tr></thead>
                    <tbody>
                    <?php foreach ($downline as $d): ?>
                        <tr><td><?= e($d['username']) ?></td><td class="small text-muted-soft"><?= time_ago($d['created_at']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($downline)): ?><tr><td colspan="2" class="text-center text-muted-soft py-4">No referrals yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">Referral Earnings</h6>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>From</th><th>Source</th><th>Level</th><th>Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($earnings as $e2): ?>
                        <tr>
                            <td><?= e($e2['from_username']) ?></td>
                            <td class="text-capitalize"><?= e($e2['source']) ?></td>
                            <td>L<?= (int) $e2['level'] ?></td>
                            <td class="text-success fw-semibold"><?= money($e2['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($earnings)): ?><tr><td colspan="4" class="text-center text-muted-soft py-4">No referral earnings yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
