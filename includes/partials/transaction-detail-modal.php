<?php
/**
 * Shared "Transaction Details" modal for wallet_ledger rows. Rows that
 * want to open it render a <tr data-ledger-row> with data-* fields
 * (see assets/js/main.js for the exact attribute names); this partial
 * only needs to be included once per page.
 */
?>
<div class="modal fade" id="txnDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:var(--surface);color:var(--text);">
            <div class="modal-header">
                <h6 class="modal-title">Transaction Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0 small">
                    <dt class="col-5" style="color:var(--text-muted);">Description</dt>
                    <dd class="col-7 text-end fw-semibold" id="txnDetailDescription">-</dd>

                    <dt class="col-5" style="color:var(--text-muted);">Wallet</dt>
                    <dd class="col-7 text-end text-capitalize" id="txnDetailWallet">-</dd>

                    <dt class="col-5" style="color:var(--text-muted);">Type</dt>
                    <dd class="col-7 text-end" id="txnDetailType">-</dd>

                    <dt class="col-5" style="color:var(--text-muted);">Amount</dt>
                    <dd class="col-7 text-end fw-bold" id="txnDetailAmount">-</dd>

                    <dt class="col-5" style="color:var(--text-muted);">Balance After</dt>
                    <dd class="col-7 text-end" id="txnDetailBalance">-</dd>

                    <dt class="col-5" style="color:var(--text-muted);">Status</dt>
                    <dd class="col-7 text-end" id="txnDetailStatus">-</dd>

                    <dt class="col-5" style="color:var(--text-muted);">Reference</dt>
                    <dd class="col-7 text-end" id="txnDetailReference">-</dd>

                    <dt class="col-5" style="color:var(--text-muted);">Date</dt>
                    <dd class="col-7 text-end" id="txnDetailDate">-</dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-brand btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
