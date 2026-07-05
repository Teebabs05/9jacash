<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Transaction;
use App\Models\Wallet;
use Exception;

class WalletController extends Controller
{
    public function index(): void
    {
        $userId = (int) current_user()['id'];
        $wallet = Wallet::forUser($userId);
        $recent = Transaction::forUserPaginated($userId, 1, 10)['rows'];

        $this->view('user/wallet', [
            'title' => 'Wallet',
            'wallet' => $wallet,
            'recent' => $recent,
        ], 'dashboard');
    }

    public function transactions(): void
    {
        $userId = (int) current_user()['id'];
        $page = max(1, (int) $this->input('page', 1));
        $walletType = $this->input('wallet_type') ?: null;
        $result = Transaction::forUserPaginated($userId, $page, 20, $walletType);

        $this->view('user/transactions', [
            'title' => 'Transaction History',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => 20,
            'walletType' => $walletType,
        ], 'dashboard');
    }

    public function transfer(): void
    {
        $this->verifyCsrf();
        $userId = (int) current_user()['id'];
        $from = (string) $this->post('from_wallet');
        $amount = (float) $this->post('amount');

        try {
            if ($amount <= 0) {
                throw new Exception('Enter a valid amount.');
            }
            Wallet::transferToMain($userId, $from, $amount);
            Session::flash('success', 'Funds transferred to your main wallet.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('wallet');
    }
}
