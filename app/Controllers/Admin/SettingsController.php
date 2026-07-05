<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Env;
use App\Core\Session;
use App\Core\Upload;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Setting;
use Exception;

class SettingsController extends Controller
{
    public function index(): void
    {
        if ($this->isPost()) {
            $this->verifyCsrf();
            $section = (string) $this->post('section');

            try {
                match ($section) {
                    'general' => $this->saveGeneral(),
                    'mail' => $this->saveMail(),
                    'payvessel' => $this->savePayVessel(),
                    'recaptcha' => $this->saveRecaptcha(),
                    'maintenance' => $this->saveMaintenance(),
                    default => throw new Exception('Unknown settings section.'),
                };
                ActivityLog::log((int) current_user()['id'], 'settings_update', "Updated {$section} settings.");
                Session::flash('success', 'Settings saved.');
            } catch (Exception $e) {
                Session::flash('error', $e->getMessage());
            }

            $this->redirect('admin/settings');
        }

        $this->view('admin/settings/index', ['title' => 'Settings'], 'admin');
    }

    private function saveGeneral(): void
    {
        $updates = [
            'site_name' => sanitize($this->post('site_name')),
            'site_tagline' => sanitize($this->post('site_tagline')),
            'currency_symbol' => sanitize($this->post('currency_symbol')),
            'currency_code' => sanitize($this->post('currency_code')),
            'contact_email' => sanitize($this->post('contact_email')),
            'contact_phone' => sanitize($this->post('contact_phone')),
            'social_facebook' => sanitize($this->post('social_facebook', '')),
            'social_twitter' => sanitize($this->post('social_twitter', '')),
            'social_instagram' => sanitize($this->post('social_instagram', '')),
            'social_telegram' => sanitize($this->post('social_telegram', '')),
            'kyc_required' => $this->post('kyc_required') ? '1' : '0',
            'email_verification_required' => $this->post('email_verification_required') ? '1' : '0',
            'registration_bonus' => (string) (float) $this->post('registration_bonus', 0),
            'min_deposit' => (string) (float) $this->post('min_deposit', 500),
            'max_deposit' => (string) (float) $this->post('max_deposit', 1000000),
            'min_withdrawal' => (string) (float) $this->post('min_withdrawal', 1000),
            'max_withdrawal' => (string) (float) $this->post('max_withdrawal', 500000),
            'daily_withdrawal_limit' => (string) (int) $this->post('daily_withdrawal_limit', 1),
            'withdrawal_charge_percent' => (string) (float) $this->post('withdrawal_charge_percent', 0),
        ];

        if (!empty($_FILES['logo']['name'])) {
            $updates['logo_path'] = Upload::image($_FILES['logo'], 'branding');
        }
        if (!empty($_FILES['favicon']['name'])) {
            $updates['favicon_path'] = Upload::image($_FILES['favicon'], 'branding');
        }

        Setting::setMany($updates);
    }

    private function saveMail(): void
    {
        Env::updateFile(BASE_PATH . '/.env', [
            'MAIL_HOST' => sanitize($this->post('mail_host', '')),
            'MAIL_PORT' => (string) (int) $this->post('mail_port', 587),
            'MAIL_ENCRYPTION' => sanitize($this->post('mail_encryption', 'tls')),
            'MAIL_USERNAME' => sanitize($this->post('mail_username', '')),
            'MAIL_PASSWORD' => (string) $this->post('mail_password', ''),
            'MAIL_FROM_ADDRESS' => sanitize($this->post('mail_from_address', '')),
            'MAIL_FROM_NAME' => sanitize($this->post('mail_from_name', '')),
        ]);
        Setting::set('smtp_configured', '1');
    }

    private function savePayVessel(): void
    {
        Env::updateFile(BASE_PATH . '/.env', [
            'PAYVESSEL_PUBLIC_KEY' => sanitize($this->post('payvessel_public_key', '')),
            'PAYVESSEL_SECRET_KEY' => (string) $this->post('payvessel_secret_key', ''),
            'PAYVESSEL_BASE_URL' => sanitize($this->post('payvessel_base_url', '')),
            'PAYVESSEL_WEBHOOK_SECRET' => (string) $this->post('payvessel_webhook_secret', ''),
        ]);
    }

    private function saveRecaptcha(): void
    {
        Env::updateFile(BASE_PATH . '/.env', [
            'RECAPTCHA_SITE_KEY' => sanitize($this->post('recaptcha_site_key', '')),
            'RECAPTCHA_SECRET_KEY' => (string) $this->post('recaptcha_secret_key', ''),
        ]);
        Setting::set('recaptcha_enabled', $this->post('recaptcha_enabled') ? '1' : '0');
    }

    private function saveMaintenance(): void
    {
        Setting::setMany([
            'maintenance_mode' => $this->post('maintenance_mode') ? '1' : '0',
        ]);
    }

    public function announcements(): void
    {
        if ($this->isPost()) {
            $this->verifyCsrf();
            Announcement::create([
                'title' => sanitize($this->post('title')),
                'message' => sanitize($this->post('message')),
                'is_active' => 1,
            ]);
            Setting::setMany([
                'announcement_text' => sanitize($this->post('message')),
                'announcement_active' => '1',
            ]);
            Session::flash('success', 'Announcement published.');
            $this->redirect('admin/announcements');
        }

        $this->view('admin/settings/announcements', [
            'title' => 'Announcements',
            'announcements' => Announcement::all('created_at DESC'),
        ], 'admin');
    }
}
