<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Security;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\OtpCode;
use App\Models\User;
use App\Models\Wallet;

class RegisterController extends Controller
{
    public function handle(): void
    {
        if (is_logged_in()) {
            $this->redirect('dashboard');
        }

        if ($this->isPost()) {
            $this->store();
            return;
        }

        $this->view('auth/register', [
            'title' => 'Create Account',
            'refCode' => sanitize($this->input('ref', '')),
        ], 'auth');
    }

    private function store(): void
    {
        $this->verifyCsrf();

        $data = [
            'full_name' => sanitize($this->post('full_name')),
            'username' => strtolower(sanitize($this->post('username'))),
            'email' => strtolower(trim((string) $this->post('email'))),
            'phone' => sanitize($this->post('phone')),
            'country' => sanitize($this->post('country')),
            'state' => sanitize($this->post('state')),
            'referral_code' => strtoupper(sanitize($this->post('referral_code'))),
        ];
        $password = (string) $this->post('password');
        $confirm = (string) $this->post('confirm_password');

        $this->old($data);

        $errors = $this->validate($data, $password, $confirm);

        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            $this->redirect('register');
        }

        $referredBy = null;
        if ($data['referral_code'] !== '') {
            $referrer = User::findByReferralCode($data['referral_code']);
            if ($referrer) {
                $referredBy = (int) $referrer['id'];
            }
        }

        $emailVerificationRequired = setting('email_verification_required', '1') === '1';

        $userId = User::create([
            'full_name' => $data['full_name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password_hash' => Security::hashPassword($password),
            'country' => $data['country'],
            'state' => $data['state'],
            'role' => 'user',
            'status' => $emailVerificationRequired ? 'pending' : 'active',
            'referral_code' => User::generateUniqueReferralCode($data['username']),
            'referred_by' => $referredBy,
            'kyc_status' => setting('kyc_required') === '1' ? 'pending' : 'not_required',
            'email_verified_at' => $emailVerificationRequired ? null : date('Y-m-d H:i:s'),
        ]);

        Wallet::forUser((int) $userId);
        ActivityLog::log((int) $userId, 'register', 'New account registered.');

        if ($emailVerificationRequired) {
            $code = OtpCode::generate((int) $userId, 'email_verify', 30);
            $this->sendVerificationEmail($data['email'], $data['full_name'], $code);
            Session::set('pending_verification_user_id', $userId);
            Session::flash('info', 'We sent a 6-digit verification code to your email.');
            $this->redirect('verify-email');
        }

        \App\Services\Onboarding::completeSignup((int) $userId, $referredBy);

        $user = User::find((int) $userId);
        \App\Core\Auth::login($user);
        Session::flash('success', 'Welcome to ' . setting('site_name', '9JACASH') . '!');
        $this->redirect('dashboard');
    }

    private function validate(array $data, string $password, string $confirm): array
    {
        $errors = [];

        if (strlen($data['full_name']) < 3) {
            $errors[] = 'Please enter your full name.';
        }
        if (!preg_match('/^[a-z0-9_]{4,20}$/', $data['username'])) {
            $errors[] = 'Username must be 4-20 characters (letters, numbers, underscore only).';
        } elseif (User::usernameExists($data['username'])) {
            $errors[] = 'This username is already taken.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (User::emailExists($data['email'])) {
            $errors[] = 'This email is already registered.';
        }
        if (strlen($data['phone']) < 7) {
            $errors[] = 'Please enter a valid phone number.';
        }
        if (!Security::isStrongPassword($password)) {
            $errors[] = 'Password must be at least 8 characters and include upper, lower case letters and a number.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Password confirmation does not match.';
        }
        if (setting('recaptcha_enabled') === '1' && !$this->verifyRecaptcha()) {
            $errors[] = 'reCAPTCHA verification failed. Please try again.';
        }

        return $errors;
    }

    private function verifyRecaptcha(): bool
    {
        $secret = config('recaptcha.secret_key');
        $response = (string) $this->post('g-recaptcha-response', '');
        if (!$secret || !$response) {
            return false;
        }
        $verify = @file_get_contents('https://www.google.com/recaptcha/api/siteverify?' . http_build_query([
            'secret' => $secret,
            'response' => $response,
            'remoteip' => Security::clientIp(),
        ]));
        $result = json_decode((string) $verify, true);
        return !empty($result['success']);
    }

    private function sendVerificationEmail(string $email, string $name, string $code): void
    {
        $body = "<p>Hi " . e($name) . ",</p><p>Your verification code is:</p>
                 <h1 style='letter-spacing:6px;color:#0D47A1;'>{$code}</h1>
                 <p>This code expires in 30 minutes. If you didn't request this, please ignore this email.</p>";
        \App\Core\Mailer::send($email, 'Verify your ' . setting('site_name', '9JACASH') . ' account', \App\Core\Mailer::template('Verify Your Email', $body));
    }
}
