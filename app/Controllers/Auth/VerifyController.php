<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Onboarding;

class VerifyController extends Controller
{
    public function handle(): void
    {
        $userId = Session::get('pending_verification_user_id');
        if (!$userId) {
            $this->redirect('login');
        }
        $user = User::find((int) $userId);
        if (!$user) {
            $this->redirect('register');
        }

        if ($this->isPost()) {
            $this->verify((int) $userId, $user);
            return;
        }

        $this->view('auth/verify-email', ['title' => 'Verify Email', 'email' => $user['email']], 'auth');
    }

    private function verify(int $userId, array $user): void
    {
        $this->verifyCsrf();
        $code = trim((string) $this->post('code'));

        if (RateLimiter::tooManyAttempts('verify_' . $userId, 'email_verify', 6, 15)) {
            Session::flash('error', 'Too many attempts. Please request a new code shortly.');
            $this->redirect('verify-email');
        }

        if (!OtpCode::verify($userId, 'email_verify', $code)) {
            RateLimiter::hit('verify_' . $userId, 'email_verify', false);
            Session::flash('error', 'Invalid or expired verification code.');
            $this->redirect('verify-email');
        }

        db()->update('users', [
            'status' => 'active',
            'email_verified_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $userId]);

        Session::remove('pending_verification_user_id');
        ActivityLog::log($userId, 'email_verified', 'Email address verified.');

        Onboarding::completeSignup($userId, $user['referred_by'] ? (int) $user['referred_by'] : null);

        $freshUser = User::find($userId);
        Auth::login($freshUser);
        Session::flash('success', 'Email verified successfully! Welcome aboard.');
        $this->redirect('dashboard');
    }

    public function resend(): void
    {
        $this->verifyCsrf();
        $userId = Session::get('pending_verification_user_id');
        if (!$userId) {
            $this->redirect('login');
        }

        if (RateLimiter::tooManyAttempts('resend_' . $userId, 'email_verify_resend', 3, 10)) {
            Session::flash('error', 'Please wait a few minutes before requesting another code.');
            $this->redirect('verify-email');
        }
        RateLimiter::hit('resend_' . $userId, 'email_verify_resend', false);

        $user = User::find((int) $userId);
        $code = OtpCode::generate((int) $userId, 'email_verify', 30);

        $body = "<p>Hi " . e($user['full_name']) . ",</p><p>Your new verification code is:</p>
                 <h1 style='letter-spacing:6px;color:#0D47A1;'>{$code}</h1><p>This code expires in 30 minutes.</p>";
        Mailer::send($user['email'], 'Your verification code', Mailer::template('Verify Your Email', $body));

        Session::flash('success', 'A new verification code has been sent to your email.');
        $this->redirect('verify-email');
    }
}
