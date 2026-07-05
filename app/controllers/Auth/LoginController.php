<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Security;
use App\Core\Session;
use App\Core\Totp;
use App\Models\ActivityLog;
use App\Models\User;

class LoginController extends Controller
{
    public function handle(): void
    {
        if (is_logged_in()) {
            $this->redirect(is_admin() ? 'admin' : 'dashboard');
        }

        if ($this->isPost()) {
            $this->attempt();
            return;
        }

        $this->view('auth/login', ['title' => 'Login'], 'auth');
    }

    private function attempt(): void
    {
        $this->verifyCsrf();

        $login = strtolower(trim((string) $this->post('login')));
        $password = (string) $this->post('password');

        if (RateLimiter::tooManyAttempts($login, 'login', 5, 15)) {
            $wait = ceil(RateLimiter::secondsUntilRetry($login, 'login', 15) / 60);
            Session::flash('error', "Too many failed attempts. Please try again in {$wait} minute(s).");
            $this->redirect('login');
        }

        $user = User::findByLogin($login);

        if (!$user || !Security::verifyPassword($password, $user['password_hash'])) {
            RateLimiter::hit($login, 'login', false);
            ActivityLog::log($user['id'] ?? null, 'login_failed', "Failed login attempt for '{$login}'.");
            Session::flash('error', 'Invalid username/email or password.');
            $this->redirect('login');
        }

        if ($user['status'] === 'suspended') {
            Session::flash('error', 'Your account has been suspended. Please contact support.');
            $this->redirect('login');
        }

        if ($user['status'] === 'pending') {
            Session::set('pending_verification_user_id', $user['id']);
            Session::flash('info', 'Please verify your email to continue.');
            $this->redirect('verify-email');
        }

        RateLimiter::clear($login, 'login');

        if ((bool) $user['two_fa_enabled']) {
            Session::set('pending_2fa_user_id', $user['id']);
            $this->redirect('login/2fa');
        }

        $this->completeLogin($user);
    }

    public function twoFactor(): void
    {
        $userId = Session::get('pending_2fa_user_id');
        if (!$userId) {
            $this->redirect('login');
        }

        if ($this->isPost()) {
            $this->verifyCsrf();
            $code = (string) $this->post('code');
            $user = User::find((int) $userId);

            if ($user && Totp::verify($user['two_fa_secret'], $code)) {
                Session::remove('pending_2fa_user_id');
                $this->completeLogin($user);
                return;
            }

            Session::flash('error', 'Invalid authentication code.');
            $this->redirect('login/2fa');
        }

        $this->view('auth/two-factor', ['title' => 'Two-Factor Verification'], 'auth');
    }

    private function completeLogin(array $user): void
    {
        db()->update('users', [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => Security::clientIp(),
        ], 'id = :id', ['id' => $user['id']]);

        Auth::login($user);
        ActivityLog::log((int) $user['id'], 'login', 'Successful login from ' . Security::clientIp() . ' (' . Security::deviceType() . ').');

        Session::flash('success', 'Welcome back, ' . $user['full_name'] . '!');

        if ((bool) $user['force_password_change']) {
            $this->redirect('profile/password');
        }

        $this->redirect($user['role'] === 'admin' ? 'admin' : 'dashboard');
    }

    public function logout(): void
    {
        $this->verifyCsrf();
        $user = current_user();
        if ($user) {
            ActivityLog::log((int) $user['id'], 'logout', 'User logged out.');
        }
        Auth::logout();
        $this->redirect('login');
    }
}
