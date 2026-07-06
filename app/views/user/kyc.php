<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">KYC Verification</h6>

            <?php if ($latest): ?>
                <div class="alert <?= $latest['status'] === 'approved' ? 'alert-success' : ($latest['status'] === 'rejected' ? 'alert-danger' : 'alert-warning') ?> small">
                    Latest submission status: <strong class="text-capitalize"><?= e($latest['status']) ?></strong>
                    <?php if ($latest['status'] === 'rejected' && $latest['admin_note']): ?>
                        <br>Reason: <?= e($latest['admin_note']) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!$latest || $latest['status'] === 'rejected'): ?>
            <form method="post" action="<?= base_url('kyc') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Document Type</label>
                    <select name="document_type" class="form-select" required>
                        <option value="national_id">National ID</option>
                        <option value="drivers_license">Driver's License</option>
                        <option value="passport">International Passport</option>
                        <option value="voters_card">Voter's Card</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Upload Document (JPG, PNG or PDF, max 5MB)</label>
                    <input type="file" name="document" class="form-control" accept="image/*,.pdf" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Upload Selfie <span class="text-muted-soft fw-normal">(optional)</span></label>
                    <input type="file" name="selfie" class="form-control" accept="image/*">
                </div>
                <button class="btn btn-brand rounded-pill px-4" type="submit">Submit for Review</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
