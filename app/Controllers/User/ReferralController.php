<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Controller;
use App\Models\ReferralEarning;
use App\Models\User;

class ReferralController extends Controller
{
    public function index(): void
    {
        $user = current_user();
        $userId = (int) $user['id'];
        $fullUser = User::find($userId);

        $this->view('user/referrals', [
            'title' => 'Referrals',
            'referralLink' => base_url('register?ref=' . $fullUser['referral_code']),
            'referralCode' => $fullUser['referral_code'],
            'downline' => User::downline($userId),
            'earnings' => ReferralEarning::forUser($userId),
            'totalEarned' => ReferralEarning::totalForUser($userId),
        ], 'dashboard');
    }

    public function leaderboard(): void
    {
        $this->view('user/leaderboard', [
            'title' => 'Leaderboard',
            'leaders' => ReferralEarning::leaderboard(20),
        ], 'dashboard');
    }
}
