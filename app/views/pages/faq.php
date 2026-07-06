<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="fw-bold mb-4 text-center">Frequently Asked Questions</h1>
            <div class="accordion" id="faqAccordion">
                <?php
                $faqs = [
                    ['q' => 'How do I start earning?', 'a' => 'Create a free account, verify your email, then deposit funds or complete free tasks to start earning immediately.'],
                    ['q' => 'How do mining plans work?', 'a' => 'Each mining plan pays a fixed daily profit into your mining wallet automatically for the plan duration. You can transfer earnings to your main wallet anytime.'],
                    ['q' => 'How long do withdrawals take?', 'a' => 'Withdrawal requests are reviewed and processed by our team, typically within 24 hours.'],
                    ['q' => 'Is there a referral program?', 'a' => 'Yes — share your referral link to earn a signup bonus plus a percentage of your referrals\' deposits, mining and task earnings across multiple levels.'],
                    ['q' => 'Do I need to complete KYC?', 'a' => 'KYC may be required before withdrawing, depending on current platform settings. You will be notified in your dashboard if it applies to you.'],
                    ['q' => 'Is my data safe?', 'a' => 'Yes. We use encrypted passwords, CSRF protection, secure file uploads, and optional two-factor authentication to keep your account safe.'],
                ];
                ?>
                <?php foreach ($faqs as $i => $f): ?>
                <div class="accordion-item mb-2 surface-card overflow-hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
                            <?= e($f['q']) ?>
                        </button>
                    </h2>
                    <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted-soft"><?= e($f['a']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
