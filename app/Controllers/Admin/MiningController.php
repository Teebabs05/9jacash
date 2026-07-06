<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Upload;
use App\Models\ActivityLog;
use App\Models\MiningPlan;
use App\Models\MiningPurchase;
use App\Services\MiningService;
use Exception;

class MiningController extends Controller
{
    public function plans(): void
    {
        if ($this->isPost()) {
            $this->verifyCsrf();
            $this->storePlan();
            return;
        }

        $this->view('admin/mining/plans', [
            'title' => 'Mining Plans',
            'plans' => MiningPlan::all('id DESC'),
        ], 'admin');
    }

    private function storePlan(): void
    {
        $data = [
            'name' => sanitize($this->post('name')),
            'price' => (float) $this->post('price'),
            'daily_profit' => (float) $this->post('daily_profit'),
            'duration_days' => (int) $this->post('duration_days'),
            'total_roi_percent' => (float) $this->post('total_roi_percent'),
            'max_users' => $this->post('max_users') !== '' ? (int) $this->post('max_users') : null,
            'status' => 'active',
        ];

        try {
            if (!empty($_FILES['image']['name'])) {
                $data['image'] = Upload::image($_FILES['image'], 'plans');
            }
            MiningPlan::create($data);
            ActivityLog::log((int) current_user()['id'], 'mining_plan_created', "Created plan '{$data['name']}'.");
            Session::flash('success', 'Mining plan created.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('admin/mining/plans');
    }

    public function updatePlan(int $id): void
    {
        $this->verifyCsrf();
        $data = [
            'name' => sanitize($this->post('name')),
            'price' => (float) $this->post('price'),
            'daily_profit' => (float) $this->post('daily_profit'),
            'duration_days' => (int) $this->post('duration_days'),
            'total_roi_percent' => (float) $this->post('total_roi_percent'),
            'max_users' => $this->post('max_users') !== '' ? (int) $this->post('max_users') : null,
        ];

        try {
            if (!empty($_FILES['image']['name'])) {
                $data['image'] = Upload::image($_FILES['image'], 'plans');
            }
            MiningPlan::updateById($id, $data);
            Session::flash('success', 'Plan updated.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('admin/mining/plans');
    }

    public function deletePlan(int $id): void
    {
        $this->verifyCsrf();
        MiningPlan::deleteById($id);
        Session::flash('success', 'Plan deleted.');
        $this->redirect('admin/mining/plans');
    }

    public function togglePlan(int $id): void
    {
        $this->verifyCsrf();
        $plan = MiningPlan::find($id);
        if ($plan) {
            MiningPlan::updateById($id, ['status' => $plan['status'] === 'active' ? 'inactive' : 'active']);
        }
        $this->redirect('admin/mining/plans');
    }

    public function purchases(): void
    {
        $page = max(1, (int) $this->input('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $rows = db()->fetchAll(
            "SELECT mining_purchases.*, mining_plans.name as plan_name, users.username
             FROM mining_purchases
             JOIN mining_plans ON mining_plans.id = mining_purchases.plan_id
             JOIN users ON users.id = mining_purchases.user_id
             ORDER BY mining_purchases.created_at DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $total = (int) (db()->fetch('SELECT COUNT(*) c FROM mining_purchases')['c'] ?? 0);

        $this->view('admin/mining/purchases', [
            'title' => 'Mining Purchases',
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ], 'admin');
    }

    public function forceComplete(int $id): void
    {
        $this->verifyCsrf();
        try {
            MiningService::forceComplete($id);
            ActivityLog::log((int) current_user()['id'], 'mining_force_complete', "Force completed purchase #{$id}.");
            Session::flash('success', 'Mining purchase marked as completed.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('admin/mining/purchases');
    }
}
