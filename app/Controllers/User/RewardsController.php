<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Controller;
use App\Models\DailyCheckin;
use App\Models\SpinHistory;
use App\Services\RewardsService;
use Exception;

class RewardsController extends Controller
{
    public function index(): void
    {
        $userId = (int) current_user()['id'];
        $this->view('user/rewards', [
            'title' => 'Daily Rewards',
            'checkedInToday' => (bool) DailyCheckin::todayFor($userId),
            'streak' => DailyCheckin::currentStreak($userId),
            'spunToday' => SpinHistory::hasSpunToday($userId),
        ], 'dashboard');
    }

    public function checkin(): void
    {
        $this->verifyCsrf();
        try {
            $reward = RewardsService::checkin((int) current_user()['id']);
            $this->json(['success' => true, 'message' => 'You earned ' . money($reward) . '!', 'reward' => $reward]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function spin(): void
    {
        $this->verifyCsrf();
        try {
            $reward = RewardsService::spin((int) current_user()['id']);
            $this->json(['success' => true, 'message' => 'You won ' . money($reward) . '!', 'reward' => $reward]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
