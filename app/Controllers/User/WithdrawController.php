<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\UserBankAccount;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Exception;

class WithdrawController extends Controller
{
    public function index(): void
    {
        $userId = (int) current_user()['id'];
        $this->view('user/withdraw', [
            'title' => 'Withdraw',
            'wallet' => Wallet::forUser($userId),
            'bankAccounts' => UserBankAccount::forUser($userId),
            'minWithdrawal' => (float) setting('min_withdrawal', 1000),
            'maxWithdrawal' => (float) setting('max_withdrawal', 500000),
            'chargePercent' => (float) setting('withdrawal_charge_percent', 0),
            'dailyLimit' => (int) setting('daily_withdrawal_limit', 1),
            'todayCount' => Withdrawal::count("user_id = :uid AND DATE(created_at) = CURDATE() AND status != 'rejected'", ['uid' => $userId]),
            'recent' => Withdrawal::paginated(1, 5, null, $userId)['rows'],
        ], 'dashboard');
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $userId = (int) current_user()['id'];
        $amount = (float) $this->post('amount');
        $bankAccountId = (int) $this->post('bank_account_id');

        try {
            $accounts = UserBankAccount::forUser($userId);
            $bankAccount = null;
            foreach ($accounts as $acc) {
                if ((int) $acc['id'] === $bankAccountId) {
                    $bankAccount = $acc;
                    break;
                }
            }
            if (!$bankAccount) {
                throw new Exception('Please select a valid bank account.');
            }

            if (!empty(current_user()['kyc_status']) && setting('kyc_required') === '1' && current_user()['kyc_status'] !== 'approved') {
                throw new Exception('Please complete KYC verification before withdrawing.');
            }

            WithdrawalService::request($userId, $amount, $bankAccount);
            ActivityLog::log($userId, 'withdrawal_requested', 'Withdrawal of ' . money($amount) . ' requested.');
            Session::flash('success', 'Your withdrawal request has been submitted.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('withdraw');
    }

    public function addBankAccount(): void
    {
        $this->verifyCsrf();
        $userId = (int) current_user()['id'];

        $id = UserBankAccount::create([
            'user_id' => $userId,
            'bank_name' => sanitize($this->post('bank_name')),
            'account_number' => sanitize($this->post('account_number')),
            'account_name' => sanitize($this->post('account_name')),
            'is_default' => 0,
        ]);

        if (count(UserBankAccount::forUser($userId)) === 1) {
            UserBankAccount::setDefault($userId, (int) $id);
        }

        Session::flash('success', 'Bank account added.');
        $this->redirect('withdraw');
    }

    public function setDefaultBankAccount(int $id): void
    {
        $this->verifyCsrf();
        UserBankAccount::setDefault((int) current_user()['id'], $id);
        Session::flash('success', 'Default bank account updated.');
        $this->redirect('withdraw');
    }

    public function history(): void
    {
        $userId = (int) current_user()['id'];
        $page = max(1, (int) $this->input('page', 1));
        $result = Withdrawal::paginated($page, 20, null, $userId);

        $this->view('user/withdraw-history', [
            'title' => 'Withdrawal History',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => 20,
        ], 'dashboard');
    }
}
