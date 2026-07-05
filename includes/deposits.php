<?php
/**
 * Deposit primitives shared by the user-facing deposit pages, the
 * PayVessel webhook, and the admin approval queue.
 */

declare(strict_types=1);

if (!function_exists('deposits_generate_reference')) {
    function deposits_generate_reference(): string
    {
        return 'DEP-' . strtoupper(bin2hex(random_bytes(6)));
    }
}

/**
 * Create a pending manual deposit (bank transfer or USDT) awaiting admin review.
 */
if (!function_exists('deposits_create_manual')) {
    function deposits_create_manual(int $userId, string $method, float $amount, ?string $proofPath): array
    {
        $reference = deposits_generate_reference();

        $stmt = db()->prepare(
            'INSERT INTO deposits (user_id, method, amount, charge, reference, proof, status, created_at, updated_at)
             VALUES (?, ?, ?, 0.00, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([$userId, $method, $amount, $reference, $proofPath, STATUS_PENDING]);

        log_activity($userId, null, 'deposit_submitted', "Submitted {$method} deposit of " . money($amount) . " (ref {$reference})");

        return ['success' => true, 'reference' => $reference, 'id' => (int) db()->lastInsertId()];
    }
}

/**
 * Create a pending PayVessel deposit row tied to the tracking reference
 * returned when the reserved account was generated.
 */
if (!function_exists('deposits_create_payvessel')) {
    function deposits_create_payvessel(int $userId, float $amount, string $trackingReference, array $gatewayResponse): int
    {
        $stmt = db()->prepare(
            'INSERT INTO deposits (user_id, method, amount, charge, reference, gateway_response, status, created_at, updated_at)
             VALUES (?, ?, ?, 0.00, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([$userId, METHOD_PAYVESSEL, $amount, $trackingReference, json_encode($gatewayResponse), STATUS_PENDING]);

        return (int) db()->lastInsertId();
    }
}

/**
 * Approve a deposit: credit the main wallet, notify the user, email them.
 * Idempotent — approving an already-approved deposit is a no-op.
 */
if (!function_exists('deposits_approve')) {
    function deposits_approve(int $depositId, ?int $adminId = null, ?float $confirmedAmount = null): array
    {
        $stmt = db()->prepare('SELECT * FROM deposits WHERE id = ? LIMIT 1');
        $stmt->execute([$depositId]);
        $deposit = $stmt->fetch();

        if (!$deposit) {
            return ['success' => false, 'message' => 'Deposit not found.'];
        }

        if ($deposit['status'] !== STATUS_PENDING) {
            return ['success' => false, 'message' => 'This deposit has already been reviewed.'];
        }

        $amount = $confirmedAmount ?? (float) $deposit['amount'];

        db()->prepare("UPDATE deposits SET status = 'approved', amount = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$amount, $depositId]);

        wallet_credit(
            (int) $deposit['user_id'],
            WALLET_MAIN,
            $amount,
            LEDGER_SOURCE_DEPOSIT,
            'Deposit approved (' . strtoupper($deposit['method']) . ', ref ' . $deposit['reference'] . ')',
            $deposit['reference']
        );

        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$deposit['user_id']]);
        $user = $stmt->fetch();

        notify_user((int) $deposit['user_id'], 'Deposit Approved', 'Your deposit of ' . money($amount) . ' has been approved and credited to your wallet.', NOTIFY_TYPE_DEPOSIT);
        if ($user) {
            Mailer::sendDepositEmail($user['email'], $user['full_name'], $amount, 'approved');
        }

        log_activity((int) $deposit['user_id'], $adminId, 'deposit_approved', "Deposit #{$depositId} approved (" . money($amount) . ')');

        return ['success' => true, 'message' => 'Deposit approved and wallet credited.'];
    }
}

/**
 * Reject a pending deposit with an optional admin-facing reason.
 */
if (!function_exists('deposits_reject')) {
    function deposits_reject(int $depositId, ?int $adminId, string $note = ''): array
    {
        $stmt = db()->prepare('SELECT * FROM deposits WHERE id = ? LIMIT 1');
        $stmt->execute([$depositId]);
        $deposit = $stmt->fetch();

        if (!$deposit) {
            return ['success' => false, 'message' => 'Deposit not found.'];
        }

        if ($deposit['status'] !== STATUS_PENDING) {
            return ['success' => false, 'message' => 'This deposit has already been reviewed.'];
        }

        db()->prepare("UPDATE deposits SET status = 'rejected', admin_note = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$note, $depositId]);

        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$deposit['user_id']]);
        $user = $stmt->fetch();

        notify_user((int) $deposit['user_id'], 'Deposit Rejected', 'Your deposit of ' . money($deposit['amount']) . ' was rejected.' . ($note ? " Reason: {$note}" : ''), NOTIFY_TYPE_DEPOSIT);
        if ($user) {
            Mailer::sendDepositEmail($user['email'], $user['full_name'], (float) $deposit['amount'], 'rejected');
        }

        log_activity((int) $deposit['user_id'], $adminId, 'deposit_rejected', "Deposit #{$depositId} rejected");

        return ['success' => true, 'message' => 'Deposit rejected.'];
    }
}

/**
 * Handle a verified PayVessel webhook notification: locate the matching
 * pending deposit by tracking reference and approve it. Safe to call
 * more than once for the same reference (idempotent).
 */
if (!function_exists('deposits_handle_payvessel_notification')) {
    function deposits_handle_payvessel_notification(string $reference, float $settledAmount): array
    {
        $stmt = db()->prepare('SELECT * FROM deposits WHERE reference = ? LIMIT 1');
        $stmt->execute([$reference]);
        $deposit = $stmt->fetch();

        if (!$deposit) {
            app_log('warning', 'PayVessel webhook: no matching deposit for reference', ['reference' => $reference]);
            return ['success' => false, 'message' => 'No matching deposit found.'];
        }

        if ($deposit['status'] !== STATUS_PENDING) {
            // Already processed - respond success so PayVessel stops retrying.
            return ['success' => true, 'message' => 'Already processed.'];
        }

        return deposits_approve((int) $deposit['id'], null, $settledAmount);
    }
}
