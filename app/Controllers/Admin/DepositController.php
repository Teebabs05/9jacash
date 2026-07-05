<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\Deposit;
use App\Models\PaymentMethod;
use App\Services\DepositService;
use Exception;

class DepositController extends Controller
{
    public function index(): void
    {
        $page = max(1, (int) $this->input('page', 1));
        $status = $this->input('status') ?: null;
        $result = Deposit::paginated($page, 20, $status);

        $this->view('admin/deposits/index', [
            'title' => 'Deposits',
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
            DepositService::approve($id, (int) current_user()['id'], sanitize($this->post('note', '')));
            ActivityLog::log((int) current_user()['id'], 'deposit_approved', "Approved deposit #{$id}.");
            Session::flash('success', 'Deposit approved and wallet credited.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('admin/deposits');
    }

    public function reject(int $id): void
    {
        $this->verifyCsrf();
        try {
            $note = sanitize($this->post('note', 'Rejected by admin.'));
            DepositService::reject($id, (int) current_user()['id'], $note ?: 'Rejected by admin.');
            ActivityLog::log((int) current_user()['id'], 'deposit_rejected', "Rejected deposit #{$id}.");
            Session::flash('success', 'Deposit rejected.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('admin/deposits');
    }

    public function edit(int $id): void
    {
        $this->verifyCsrf();
        $deposit = Deposit::find($id);
        if (!$deposit) {
            Session::flash('error', 'Deposit not found.');
            $this->redirect('admin/deposits');
        }
        $amount = (float) $this->post('amount');
        if ($amount > 0) {
            Deposit::updateById($id, ['amount' => $amount]);
            ActivityLog::log((int) current_user()['id'], 'deposit_edited', "Edited deposit #{$id} amount to {$amount}.");
            Session::flash('success', 'Deposit updated.');
        }
        $this->redirect('admin/deposits');
    }

    public function export(): void
    {
        $rows = Deposit::paginated(1, 100000, $this->input('status') ?: null)['rows'];

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="deposits.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'User', 'Amount', 'Method', 'Reference', 'Status', 'Date']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['id'], $r['username'], $r['amount'], $r['method'], $r['reference'], $r['status'], $r['created_at']]);
        }
        fclose($out);
        exit;
    }

    public function paymentMethods(): void
    {
        if ($this->isPost()) {
            $this->verifyCsrf();
            PaymentMethod::create([
                'bank_name' => sanitize($this->post('bank_name')),
                'account_name' => sanitize($this->post('account_name')),
                'account_number' => sanitize($this->post('account_number')),
                'instructions' => sanitize($this->post('instructions')),
                'is_active' => 1,
            ]);
            Session::flash('success', 'Payment method added.');
            $this->redirect('admin/payment-methods');
        }

        $this->view('admin/deposits/payment-methods', [
            'title' => 'Payment Methods',
            'methods' => PaymentMethod::all('id DESC'),
        ], 'admin');
    }

    public function deletePaymentMethod(int $id): void
    {
        $this->verifyCsrf();
        PaymentMethod::deleteById($id);
        Session::flash('success', 'Payment method removed.');
        $this->redirect('admin/payment-methods');
    }
}
