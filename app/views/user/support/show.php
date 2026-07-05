<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="surface-card p-4 mb-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="fw-bold mb-1"><?= e($ticket['subject']) ?></h6>
                    <span class="badge badge-status-<?= $ticket['status'] === 'closed' ? 'suspended' : ($ticket['status'] === 'answered' ? 'approved' : 'pending') ?> text-capitalize"><?= e($ticket['status']) ?></span>
                </div>
                <?php if ($ticket['status'] !== 'closed'): ?>
                <form method="post" action="<?= base_url('support/' . $ticket['id'] . '/close') ?>" onsubmit="return confirm('Close this ticket?')">
                    <?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary">Close Ticket</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="surface-card p-4 mb-3">
            <?php foreach ($messages as $m): ?>
                <div class="d-flex <?= $m['sender_type'] === 'admin' ? 'justify-content-start' : 'justify-content-end' ?> mb-3">
                    <div class="p-3 rounded-3" style="max-width:75%; background:<?= $m['sender_type'] === 'admin' ? 'var(--bg-surface-alt)' : 'var(--c-primary)' ?>; color:<?= $m['sender_type'] === 'admin' ? 'var(--text-primary)' : '#fff' ?>;">
                        <div class="small fw-semibold mb-1"><?= $m['sender_type'] === 'admin' ? 'Support Team' : 'You' ?></div>
                        <div><?= nl2br(e($m['message'])) ?></div>
                        <?php if ($m['attachment']): ?><a href="<?= base_url('files/proofs/' . basename($m['attachment'])) ?>" target="_blank" class="d-block small mt-1" style="color:inherit;text-decoration:underline;">View Attachment</a><?php endif; ?>
                        <div class="small mt-1 opacity-75"><?= time_ago($m['created_at']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($ticket['status'] !== 'closed'): ?>
        <div class="surface-card p-4">
            <form method="post" action="<?= base_url('support/' . $ticket['id'] . '/reply') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <textarea name="message" class="form-control mb-2" rows="3" placeholder="Type your reply..." required></textarea>
                <div class="d-flex justify-content-between align-items-center">
                    <input type="file" name="attachment" class="form-control form-control-sm w-auto" accept="image/*">
                    <button class="btn btn-brand rounded-pill px-4">Send Reply</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>
