<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\MiningPlan;
use App\Models\MiningPurchase;
use App\Services\MiningService;
use Exception;

class MiningController extends Controller
{
    public function index(): void
    {
        $userId = (int) current_user()['id'];
        $this->view('user/mining', [
            'title' => 'Mining Plans',
            'plans' => MiningPlan::activePlans(),
            'purchases' => MiningPurchase::activeForUser($userId),
        ], 'dashboard');
    }

    public function buy(int $id): void
    {
        $this->verifyCsrf();
        $userId = (int) current_user()['id'];
        try {
            MiningService::purchase($userId, $id);
            ActivityLog::log($userId, 'mining_purchase', "Purchased mining plan #{$id}.");
            Session::flash('success', 'Mining plan activated successfully!');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('mining');
    }

    public function renew(int $id): void
    {
        $this->verifyCsrf();
        $userId = (int) current_user()['id'];
        $purchase = MiningPurchase::find($id);

        if (!$purchase || (int) $purchase['user_id'] !== $userId) {
            Session::flash('error', 'Mining purchase not found.');
            $this->redirect('mining');
        }

        try {
            MiningService::purchase($userId, (int) $purchase['plan_id']);
            Session::flash('success', 'Plan renewed successfully!');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('mining');
    }

    public function history(): void
    {
        $userId = (int) current_user()['id'];
        $this->view('user/mining-history', [
            'title' => 'Mining History',
            'purchases' => MiningPurchase::activeForUser($userId),
        ], 'dashboard');
    }
}
