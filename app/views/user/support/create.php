<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">New Support Ticket</h6>
            <form method="post" action="<?= base_url('support/new') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label small fw-semibold">Subject</label><input type="text" name="subject" class="form-control" required></div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Category</label>
                        <select name="category" class="form-select">
                            <option value="general">General</option>
                            <option value="deposit">Deposit</option>
                            <option value="withdrawal">Withdrawal</option>
                            <option value="mining">Mining</option>
                            <option value="task">Task</option>
                            <option value="account">Account</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3"><label class="form-label small fw-semibold">Message</label><textarea name="message" class="form-control" rows="5" required></textarea></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Attachment <span class="text-muted-soft fw-normal">(optional)</span></label><input type="file" name="attachment" class="form-control" accept="image/*"></div>
                <button class="btn btn-brand rounded-pill px-4" type="submit">Submit Ticket</button>
            </form>
        </div>
    </div>
</div>
