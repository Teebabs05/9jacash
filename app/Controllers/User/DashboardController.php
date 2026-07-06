<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Controller;
use App\Models\MiningPurchase;
use App\Models\ReferralEarning;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

class DashboardController extends Controller
{
    public function index(): void
    {
        $userId = (int) current_user()['id'];
        $wallet = Wallet::forUser($userId);
        $activeMining = MiningPurchase::activeForUser($userId);
        $recentTx = Transaction::forUserPaginated($userId, 1, 8)['rows'];
        $referralCount = count(User::downline($userId));
        $referralEarnings = ReferralEarning::totalForUser($userId);

        $chart = db()->fetchAll(
            "SELECT DATE(created_at) d, SUM(amount) total FROM transactions
             WHERE user_id = :uid AND type = 'credit' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY DATE(created_at) ORDER BY d ASC",
            ['uid' => $userId]
        );

        $this->view('user/dashboard', [
            'title' => 'Dashboard',
            'wallet' => $wallet,
            'activeMining' => $activeMining,
            'recentTx' => $recentTx,
            'referralCount' => $referralCount,
            'referralEarnings' => $referralEarnings,
            'chartLabels' => array_map(fn($r) => date('M j', strtotime($r['d'])), $chart),
            'chartData' => array_map(fn($r) => (float) $r['total'], $chart),
        ], 'dashboard');
    }
}
