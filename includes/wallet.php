<?php
/**
 * Core wallet ledger primitives shared by every earning/spending module
 * (mining, tasks, ads, spin, check-in, referrals, deposits, withdrawals).
 *
 * Every balance mutation MUST go through wallet_credit()/wallet_debit()
 * so that the wallets table and the wallet_ledger audit trail never
 * drift apart. Both run inside a transaction.
 */

declare(strict_types=1);

const WALLET_COLUMN_MAP = [
    WALLET_MAIN     => 'main_balance',
    WALLET_BONUS    => 'bonus_balance',
    WALLET_REFERRAL => 'referral_balance',
    WALLET_MINING   => 'mining_balance',
    WALLET_PENDING  => 'pending_balance',
];

/**
 * Fetch (or lazily create) the wallet row for a user.
 */
if (!function_exists('get_wallet')) {
    function get_wallet(int $userId): array
    {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM wallets WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $wallet = $stmt->fetch();

        if (!$wallet) {
            $pdo->prepare('INSERT INTO wallets (user_id) VALUES (?)')->execute([$userId]);
            $stmt->execute([$userId]);
            $wallet = $stmt->fetch();
        }

        return $wallet;
    }
}

/**
 * Total spendable balance (main + bonus + referral + mining), excluding pending.
 */
if (!function_exists('wallet_total_balance')) {
    function wallet_total_balance(int $userId): float
    {
        $wallet = get_wallet($userId);

        return (float) $wallet['main_balance']
            + (float) $wallet['bonus_balance']
            + (float) $wallet['referral_balance']
            + (float) $wallet['mining_balance'];
    }
}

/**
 * Credit a wallet type and write a ledger entry. Returns the new balance.
 */
if (!function_exists('wallet_credit')) {
    function wallet_credit(int $userId, string $walletType, float $amount, string $source, string $description = '', ?string $reference = null): float
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Credit amount must be positive.');
        }

        if (!isset(WALLET_COLUMN_MAP[$walletType])) {
            throw new InvalidArgumentException("Unknown wallet type: {$walletType}");
        }

        $column = WALLET_COLUMN_MAP[$walletType];
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $pdo->prepare("SELECT id FROM wallets WHERE user_id = ? FOR UPDATE")->execute([$userId]);
            $pdo->prepare("UPDATE wallets SET {$column} = {$column} + ?, updated_at = NOW() WHERE user_id = ?")
                ->execute([$amount, $userId]);

            $stmt = $pdo->prepare("SELECT {$column} AS balance FROM wallets WHERE user_id = ?");
            $stmt->execute([$userId]);
            $newBalance = (float) $stmt->fetch()['balance'];

            $stmt = $pdo->prepare(
                'INSERT INTO wallet_ledger (user_id, wallet_type, type, amount, balance_after, reference, description, source, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$userId, $walletType, LEDGER_CREDIT, $amount, $newBalance, $reference, $description, $source, STATUS_APPROVED]);

            $pdo->commit();

            return $newBalance;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}

/**
 * Debit a wallet type and write a ledger entry. Throws if funds are insufficient.
 */
if (!function_exists('wallet_debit')) {
    function wallet_debit(int $userId, string $walletType, float $amount, string $source, string $description = '', ?string $reference = null): float
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Debit amount must be positive.');
        }

        if (!isset(WALLET_COLUMN_MAP[$walletType])) {
            throw new InvalidArgumentException("Unknown wallet type: {$walletType}");
        }

        $column = WALLET_COLUMN_MAP[$walletType];
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("SELECT {$column} AS balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            $current = (float) ($row['balance'] ?? 0);

            if ($current < $amount) {
                $pdo->rollBack();
                throw new RuntimeException('Insufficient wallet balance.');
            }

            $pdo->prepare("UPDATE wallets SET {$column} = {$column} - ?, updated_at = NOW() WHERE user_id = ?")
                ->execute([$amount, $userId]);

            $newBalance = $current - $amount;

            $stmt = $pdo->prepare(
                'INSERT INTO wallet_ledger (user_id, wallet_type, type, amount, balance_after, reference, description, source, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$userId, $walletType, LEDGER_DEBIT, $amount, $newBalance, $reference, $description, $source, STATUS_APPROVED]);

            $pdo->commit();

            return $newBalance;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
