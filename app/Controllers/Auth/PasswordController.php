<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Core\Security;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\PasswordReset;
use App\Models\User;

class PasswordController extends Controller
{
    public function forgot(): void
    {
        if ($this->isPost()) {
            $this->sendResetLink();
            return;
        }
        $this->view('auth/forgot-password', ['title' => 'Forgot Password'], 'auth');
    }

    private function sendResetLink(): void
    {
        $this->verifyCsrf();
        $email = strtolower(trim((string) $this->post('email')));

        if (RateLimiter::tooManyAttempts($email, 'password_reset', 3, 15)) {
            Session::flash('error', 'Too many requests. Please try again later.');
            $this->redirect('forgot-password');
        }
        RateLimiter::hit($email, 'password_reset', false);

        $user = User::findByEmail($email);

        // Always show the same message whether or not the account exists,
        // to avoid leaking which emails are registered.
        if ($user) {
            $token = PasswordReset::generate($email, 30);
            $link = base_url('reset-password?email=' . urlencode($email) . '&token=' . $token);
            $body = "<p>Hi " . e($user['full_name']) . ",</p><p>Click the button below to reset your password. This link expires in 30 minutes.</p>
                     <p style='text-align:center;margin:24px 0;'><a href='" . e($link) . "' style='background:#0D47A1;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;'>Reset Password</a></p>
                     <p>If you didn't request this, you can safely ignore this email.</p>";
            Mailer::send($email, 'Reset your password', Mailer::template('Password Reset Request', $body));
            ActivityLog::log((int) $user['id'], 'password_reset_requested', 'Password reset link requested.');
        }

        Session::flash('success', 'If an account exists for that email, a reset link has been sent.');
        $this->redirect('forgot-password');
    }

    public function reset(): void
    {
        $email = strtolower(trim((string) $this->input('email', '')));
        $token = (string) $this->input('token', '');

        if ($this->isPost()) {
            $this->updatePassword();
            return;
        }

        $valid = $email && $token && PasswordReset::findValid($email, $token) !== false;

        $this->view('auth/reset-password', [
            'title' => 'Reset Password',
            'email' => $email,
            'token' => $token,
            'valid' => $valid,
        ], 'auth');
    }

    private function updatePassword(): void
    {
        $this->verifyCsrf();
        $email = strtolower(trim((string) $this->post('email')));
        $token = (string) $this->post('token');
        $password = (string) $this->post('password');
        $confirm = (string) $this->post('confirm_password');

        $record = PasswordReset::findValid($email, $token);
        if (!$record) {
            Session::flash('error', 'This reset link is invalid or has expired.');
            $this->redirect('forgot-password');
        }

        if (!Security::isStrongPassword($password) || $password !== $confirm) {
            Session::flash('error', 'Password must be strong (8+ chars, upper/lower/number) and match confirmation.');
            $this->redirect('reset-password?email=' . urlencode($email) . '&token=' . $token);
        }

        $user = User::findByEmail($email);
        if ($user) {
            db()->update('users', [
                'password_hash' => Security::hashPassword($password),
                'force_password_change' => 0,
            ], 'id = :id', ['id' => $user['id']]);
            ActivityLog::log((int) $user['id'], 'password_reset', 'Password reset via email link.');
        }

        PasswordReset::markUsed((int) $record['id']);

        Session::flash('success', 'Password reset successfully. You can now login.');
        $this->redirect('login');
    }
}
