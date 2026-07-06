<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Security;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\Deposit;
use App\Models\MiningPurchase;
use App\Models\Notification;
use App\Models\ReferralEarning;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;

class UserController extends Controller
{
    public function index(): void
    {
        $search = sanitize($this->input('q', ''));
        $page = max(1, (int) $this->input('page', 1));
        $result = User::searchPaginated($search, $page, 20);

        $this->view('admin/users/index', [
            'title' => 'Users',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => 20,
            'search' => $search,
        ], 'admin');
    }

    public function show(int $id): void
    {
        $user = User::find($id);
        if (!$user) {
            Session::flash('error', 'User not found.');
            $this->redirect('admin/users');
        }

        $this->view('admin/users/show', [
            'title' => 'User: ' . $user['username'],
            'user' => $user,
            'wallet' => Wallet::forUser($id),
            'downline' => User::downline($id),
            'referralEarnings' => ReferralEarning::totalForUser($id),
            'deposits' => Deposit::paginated(1, 5, null, $id)['rows'],
            'withdrawals' => Withdrawal::paginated(1, 5, null, $id)['rows'],
            'mining' => MiningPurchase::activeForUser($id),
        ], 'admin');
    }

    public function update(int $id): void
    {
        $this->verifyCsrf();
        User::updateById($id, [
            'full_name' => sanitize($this->post('full_name')),
            'phone' => sanitize($this->post('phone')),
            'country' => sanitize($this->post('country')),
            'state' => sanitize($this->post('state')),
            'kyc_status' => sanitize($this->post('kyc_status')),
        ]);
        ActivityLog::log((int) current_user()['id'], 'admin_user_update', "Updated user #{$id}.");
        Session::flash('success', 'User updated.');
        $this->redirect('admin/users/' . $id);
    }

    public function suspend(int $id): void
    {
        $this->verifyCsrf();
        User::updateById($id, ['status' => 'suspended']);
        ActivityLog::log((int) current_user()['id'], 'admin_user_suspend', "Suspended user #{$id}.");
        Notification::send($id, 'Account Suspended', 'Your account has been suspended. Contact support for more information.', 'error');
        Session::flash('success', 'User suspended.');
        $this->redirect('admin/users/' . $id);
    }

    public function activate(int $id): void
    {
        $this->verifyCsrf();
        User::updateById($id, ['status' => 'active']);
        ActivityLog::log((int) current_user()['id'], 'admin_user_activate', "Activated user #{$id}.");
        Notification::send($id, 'Account Activated', 'Your account has been reactivated.', 'success');
        Session::flash('success', 'User activated.');
        $this->redirect('admin/users/' . $id);
    }

    public function delete(int $id): void
    {
        $this->verifyCsrf();
        $user = User::find($id);
        if ($user && $user['role'] !== 'admin') {
            User::deleteById($id);
            ActivityLog::log((int) current_user()['id'], 'admin_user_delete', "Deleted user #{$id}.");
            Session::flash('success', 'User deleted.');
        } else {
            Session::flash('error', 'Cannot delete this user.');
        }
        $this->redirect('admin/users');
    }

    public function resetPassword(int $id): void
    {
        $this->verifyCsrf();
        $newPassword = bin2hex(random_bytes(5));
        User::updateById($id, [
            'password_hash' => Security::hashPassword($newPassword),
            'force_password_change' => 1,
        ]);
        ActivityLog::log((int) current_user()['id'], 'admin_password_reset', "Reset password for user #{$id}.");
        Notification::send($id, 'Password Reset', 'Your password was reset by an administrator. Please check your email or contact support for your temporary password.', 'info');
        Session::flash('success', "Password reset. Temporary password: {$newPassword}");
        $this->redirect('admin/users/' . $id);
    }

    public function loginAs(int $id): void
    {
        $target = User::find($id);
        if (!$target || $target['role'] === 'admin') {
            Session::flash('error', 'Cannot impersonate this account.');
            $this->redirect('admin/users');
        }

        Session::set('_impersonator_id', current_user()['id']);
        Auth::login($target);
        ActivityLog::log($id, 'impersonation_started', 'Admin logged in as this user.');
        $this->redirect('dashboard');
    }

    public function export(): void
    {
        $rows = User::searchPaginated('', 1, 1000000)['rows'];

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="users.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Full Name', 'Username', 'Email', 'Phone', 'Status', 'Country', 'Joined']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['id'], $r['full_name'], $r['username'], $r['email'], $r['phone'], $r['status'], $r['country'], $r['created_at']]);
        }
        fclose($out);
        exit;
    }
}
