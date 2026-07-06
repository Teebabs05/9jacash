<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Setting;

class ReferralSettingsController extends Controller
{
    public function index(): void
    {
        if ($this->isPost()) {
            $this->verifyCsrf();
            Setting::setMany([
                'referral_signup_bonus' => (float) $this->post('referral_signup_bonus'),
                'referral_deposit_percent' => (float) $this->post('referral_deposit_percent'),
                'referral_mining_percent' => (float) $this->post('referral_mining_percent'),
                'referral_task_percent' => (float) $this->post('referral_task_percent'),
                'referral_level_2_percent' => (float) $this->post('referral_level_2_percent'),
                'referral_level_3_percent' => (float) $this->post('referral_level_3_percent'),
            ]);
            Session::flash('success', 'Referral settings updated.');
            $this->redirect('admin/referral-settings');
        }

        $this->view('admin/referral-settings', ['title' => 'Referral Settings'], 'admin');
    }
}
