<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\User;

class ImpersonationController extends Controller
{
    public function returnToAdmin(): void
    {
        $this->verifyCsrf();
        $adminId = Session::get('_impersonator_id');

        if (!$adminId) {
            $this->redirect('dashboard');
        }

        $admin = User::find((int) $adminId);
        Session::remove('_impersonator_id');

        if ($admin) {
            Auth::login($admin);
            ActivityLog::log((int) $admin['id'], 'impersonation_ended', 'Returned from user impersonation.');
        }

        $this->redirect('admin/users');
    }
}
