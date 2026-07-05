<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="fw-bold mb-4 text-center">How Mining Works</h1>
            <div class="row g-4">
                <?php
                $steps = [
                    ['n' => 1, 't' => 'Choose a Plan', 'd' => 'Pick a mining plan based on your budget and desired daily return.'],
                    ['n' => 2, 't' => 'Activate Instantly', 'd' => 'Your main wallet balance is debited and the plan activates immediately.'],
                    ['n' => 3, 't' => 'Earn Daily', 'd' => 'Our system automatically credits your mining wallet every day for the plan duration.'],
                    ['n' => 4, 't' => 'Withdraw or Reinvest', 'd' => 'Transfer earnings to your main wallet to withdraw, or renew your plan to keep earning.'],
                ];
                ?>
                <?php foreach ($steps as $s): ?>
                <div class="col-md-6">
                    <div class="surface-card p-4 d-flex gap-3">
                        <span class="icon-badge flex-shrink-0"><?= $s['n'] ?></span>
                        <div><h6 class="fw-bold mb-1"><?= $s['t'] ?></h6><p class="small text-muted-soft mb-0"><?= $s['d'] ?></p></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-5"><a href="<?= base_url('pricing') ?>" class="btn btn-brand rounded-pill px-4">View Mining Plans</a></div>
        </div>
    </div>
</div>
