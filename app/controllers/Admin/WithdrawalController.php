<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Exception;

class WithdrawalController extends Controller
{
    public function index(): void
    {
        $page = max(1, (int) $this->input('page', 1));
        $status = $this->input('status') ?: null;
        $result = Withdrawal::paginated($page, 20, $status);

        $this->view('admin/withdrawals/index', [
            'title' => 'Withdrawals',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => 20,
            'status' => $status,
        ], 'admin');
    }

    public function approve(int $id): void
    {
        $this->verifyCsrf();
        try {
            WithdrawalService::approve($id, (int) current_user()['id'], sanitize($this->post('note', '')));
            ActivityLog::log((int) current_user()['id'], 'withdrawal_approved', "Approved withdrawal #{$id}.");
            Session::flash('success', 'Withdrawal marked as approved/paid.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('admin/withdrawals');
    }

    public function reject(int $id): void
    {
        $this->verifyCsrf();
        try {
            $note = sanitize($this->post('note', 'Rejected by admin.'));
            WithdrawalService::reject($id, (int) current_user()['id'], $note ?: 'Rejected by admin.');
            ActivityLog::log((int) current_user()['id'], 'withdrawal_rejected', "Rejected withdrawal #{$id}.");
            Session::flash('success', 'Withdrawal rejected and funds refunded to user.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('admin/withdrawals');
    }

    public function export(): void
    {
        $rows = Withdrawal::paginated(1, 100000, $this->input('status') ?: null)['rows'];

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="withdrawals.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'User', 'Amount', 'Charge', 'Net', 'Bank', 'Account Number', 'Status', 'Date']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['id'], $r['username'], $r['amount'], $r['charge'], $r['net_amount'], $r['bank_name'], $r['account_number'], $r['status'], $r['created_at']]);
        }
        fclose($out);
        exit;
    }
}
