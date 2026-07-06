<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();
$minDeposit = (float) get_setting('min_deposit', 500);
$maxDeposit = (float) get_setting('max_deposit', 1000000);
$payvesselEnabled = (bool) get_setting('payvessel_enabled', false) && PayVessel::isConfigured();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $method = $_POST['method'] ?? '';
    $amount = (float) ($_POST['amount'] ?? 0);

    if ($amount < $minDeposit || $amount > $maxDeposit) {
        $errors[] = 'Amount must be between ' . money($minDeposit) . ' and ' . money($maxDeposit) . '.';
    }

    if (!$errors && $method === METHOD_PAYVESSEL) {
        if (!$payvesselEnabled) {
            $errors[] = 'Automatic deposit is currently unavailable. Please use manual deposit.';
        } else {
            $result = PayVessel::createVirtualAccount($user['email'], $user['full_name'], (string) $user['phone']);

            if (!$result['success']) {
                $errors[] = $result['message'];
            } else {
                $depositId = deposits_create_payvessel(
                    (int) $user['id'],
                    $amount,
                    $result['data']['tracking_reference'],
                    $result['data']['raw']
                );
                log_activity((int) $user['id'], null, 'deposit_payvessel_initiated', 'Generated PayVessel account for ' . money($amount));
                redirect(rtrim(APP_URL, '/') . '/payments/pending.php?id=' . $depositId);
            }
        }
    } elseif (!$errors && in_array($method, [METHOD_BANK, METHOD_USDT], true)) {
        $proofPath = null;

        if (empty($_FILES['proof']['name'])) {
            $errors[] = 'Please upload proof of payment.';
        } else {
            $error = validate_upload($_FILES['proof'], ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], 5 * 1024 * 1024);
            if ($error) {
                $errors[] = $error;
            } else {
                $proofPath = store_upload($_FILES['proof'], 'receipts');
            }
        }

        if (!$errors) {
            $result = deposits_create_manual((int) $user['id'], $method, $amount, $proofPath);
            flash('deposit', 'Your deposit request (ref ' . $result['reference'] . ') has been submitted and is awaiting admin approval.', 'success');
            redirect(rtrim(APP_URL, '/') . '/payments/history.php');
        }
    } elseif (!$errors) {
        $errors[] = 'Please select a valid deposit method.';
    }
}

$bankName = get_setting('deposit_bank_name', '');
$bankAccountNumber = get_setting('deposit_bank_account_number', '');
$bankAccountName = get_setting('deposit_bank_account_name', '');
$usdtAddress = get_setting('deposit_usdt_address', '');
$usdtNetwork = get_setting('deposit_usdt_network', 'TRC20');

$pageTitle = 'Deposit Funds';
$activeNav = 'deposit';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<?php if ($errors): ?>
    <div class="alert alert-danger py-2 px-3 small mb-3">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <ul class="nav nav-pills mb-4 gap-2" id="depositTabs" role="tablist">
            <?php if ($payvesselEnabled): ?>
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-payvessel" type="button">Automatic</button></li>
            <?php endif; ?>
            <li class="nav-item"><button class="nav-link <?= !$payvesselEnabled ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-bank" type="button">Bank Transfer</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-usdt" type="button">USDT</button></li>
        </ul>

        <div class="tab-content">
            <?php if ($payvesselEnabled): ?>
            <div class="tab-pane fade show active" id="tab-payvessel">
                <div class="card-surface p-4">
                    <h5 class="fw-bold mb-1">Automatic Bank Transfer</h5>
                    <p class="small mb-3" style="color:var(--text-muted);">We'll generate a one-time account number. Transfer the exact amount and your wallet is credited automatically.</p>
                    <form method="POST" action="" data-loading-submit>
                        <?= csrf_field() ?>
                        <input type="hidden" name="method" value="payvessel">
                        <div class="row g-2 mb-4">
                            <div class="col-7">
                                <label class="form-label small">Amount (₦)</label>
                                <input type="number" step="0.01" min="<?= $minDeposit ?>" max="<?= $maxDeposit ?>" class="form-control" name="amount" data-currency-group="pv" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label small">&asymp; USD</label>
                                <input type="number" step="0.01" class="form-control" data-currency-usd="pv" placeholder="0.00">
                            </div>
                            <div class="form-text">Min <?= e(money($minDeposit)) ?> — Max <?= e(money($maxDeposit)) ?></div>
                        </div>
                        <button type="submit" class="btn btn-brand w-100">Generate Account Number</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="tab-pane fade <?= !$payvesselEnabled ? 'show active' : '' ?>" id="tab-bank">
                <div class="card-surface p-4">
                    <h5 class="fw-bold mb-1">Manual Bank Transfer</h5>
                    <?php if ($bankAccountNumber): ?>
                        <div class="alert alert-info small py-2 px-3 mb-3">
                            <strong><?= e($bankName) ?></strong><br>
                            Account Number: <strong><?= e($bankAccountNumber) ?></strong><br>
                            Account Name: <strong><?= e($bankAccountName) ?></strong>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning small py-2 px-3 mb-3">Bank details have not been configured yet. Please contact support.</div>
                    <?php endif; ?>
                    <form method="POST" action="" enctype="multipart/form-data" data-loading-submit>
                        <?= csrf_field() ?>
                        <input type="hidden" name="method" value="bank">
                        <div class="row g-2 mb-3">
                            <div class="col-7">
                                <label class="form-label small">Amount Transferred (₦)</label>
                                <input type="number" step="0.01" min="<?= $minDeposit ?>" max="<?= $maxDeposit ?>" class="form-control" name="amount" data-currency-group="bank" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label small">&asymp; USD</label>
                                <input type="number" step="0.01" class="form-control" data-currency-usd="bank" placeholder="0.00">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small">Upload Proof of Payment</label>
                            <input type="file" class="form-control" name="proof" accept="image/png,image/jpeg,image/webp,application/pdf" required>
                        </div>
                        <button type="submit" class="btn btn-brand w-100">Submit for Review</button>
                    </form>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-usdt">
                <div class="card-surface p-4">
                    <h5 class="fw-bold mb-1">USDT Deposit</h5>
                    <?php if ($usdtAddress): ?>
                        <div class="alert alert-info small py-2 px-3 mb-3">
                            Network: <strong><?= e($usdtNetwork) ?></strong><br>
                            Address: <strong style="word-break:break-all;"><?= e($usdtAddress) ?></strong>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning small py-2 px-3 mb-3">USDT address has not been configured yet. Please contact support.</div>
                    <?php endif; ?>
                    <form method="POST" action="" enctype="multipart/form-data" data-loading-submit>
                        <?= csrf_field() ?>
                        <input type="hidden" name="method" value="usdt">
                        <div class="row g-2 mb-3">
                            <div class="col-5">
                                <label class="form-label small">USDT Amount Sent</label>
                                <input type="number" step="0.01" class="form-control" data-currency-usd="usdt" placeholder="0.00">
                            </div>
                            <div class="col-7">
                                <label class="form-label small">Naira Equivalent (₦)</label>
                                <input type="number" step="0.01" min="<?= $minDeposit ?>" max="<?= $maxDeposit ?>" class="form-control" name="amount" data-currency-group="usdt" required>
                            </div>
                            <div class="form-text">Enter either field — the other fills in automatically using today's rate.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small">Upload Transaction Proof</label>
                            <input type="file" class="form-control" name="proof" accept="image/png,image/jpeg,image/webp,application/pdf" required>
                        </div>
                        <button type="submit" class="btn btn-brand w-100">Submit for Review</button>
                    </form>
                </div>
            </div>
        </div>

        <p class="text-center small mt-3"><a href="history.php" style="color:var(--brand-emerald);">View Deposit History</a></p>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
