<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Security;
use App\Core\Session;
use App\Core\Totp;
use App\Core\Upload;
use App\Models\ActivityLog;
use App\Models\User;
use Exception;

class ProfileController extends Controller
{
    public function handle(): void
    {
        $userId = (int) current_user()['id'];

        if ($this->isPost()) {
            $this->verifyCsrf();

            $data = [
                'full_name' => sanitize($this->post('full_name')),
                'phone' => sanitize($this->post('phone')),
                'country' => sanitize($this->post('country')),
                'state' => sanitize($this->post('state')),
            ];

            if (strlen($data['full_name']) < 3) {
                Session::flash('error', 'Please enter a valid full name.');
                $this->redirect('profile');
            }

            db()->update('users', $data, 'id = :id', ['id' => $userId]);
            ActivityLog::log($userId, 'profile_update', 'Profile details updated.');
            Auth::refresh();

            Session::flash('success', 'Profile updated successfully.');
            $this->redirect('profile');
        }

        $user = User::find($userId);
        $this->view('user/profile', ['title' => 'Profile', 'user' => $user], 'dashboard');
    }

    public function uploadAvatar(): void
    {
        $this->verifyCsrf();
        $userId = (int) current_user()['id'];

        if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
            Session::flash('error', 'Please choose an image to upload.');
            $this->redirect('profile');
        }

        try {
            $path = Upload::image($_FILES['avatar'], 'avatars', 2 * 1024 * 1024);
            db()->update('users', ['avatar' => $path], 'id = :id', ['id' => $userId]);
            ActivityLog::log($userId, 'avatar_update', 'Profile picture updated.');
            Auth::refresh();
            Session::flash('success', 'Profile picture updated.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('profile');
    }

    public function changePassword(): void
    {
        $userId = (int) current_user()['id'];
        $forced = (bool) (current_user()['force_password_change'] ?? false);

        if ($this->isPost()) {
            $this->verifyCsrf();
            $user = User::find($userId);

            $current = (string) $this->post('current_password');
            $new = (string) $this->post('new_password');
            $confirm = (string) $this->post('confirm_password');

            if (!Security::verifyPassword($current, $user['password_hash'])) {
                Session::flash('error', 'Your current password is incorrect.');
                $this->redirect('profile/password');
            }
            if (!Security::isStrongPassword($new) || $new !== $confirm) {
                Session::flash('error', 'New password must be strong (8+ chars, upper/lower/number) and match confirmation.');
                $this->redirect('profile/password');
            }

            db()->update('users', [
                'password_hash' => Security::hashPassword($new),
                'force_password_change' => 0,
            ], 'id = :id', ['id' => $userId]);

            ActivityLog::log($userId, 'password_change', 'Password changed from profile.');
            Auth::refresh();
            Session::flash('success', 'Password updated successfully.');
            $this->redirect($forced ? 'dashboard' : 'profile/password');
        }

        $this->view('user/change-password', ['title' => 'Change Password', 'forced' => $forced], 'dashboard');
    }

    public function twoFactor(): void
    {
        $userId = (int) current_user()['id'];
        $user = User::find($userId);

        if ($this->isPost()) {
            $this->verifyCsrf();
            $code = (string) $this->post('code');
            $secret = (string) Session::get('_2fa_setup_secret');

            if (!$secret || !Totp::verify($secret, $code)) {
                Session::flash('error', 'Invalid code. Please try again.');
                $this->redirect('profile/2fa');
            }

            db()->update('users', ['two_fa_enabled' => 1, 'two_fa_secret' => $secret], 'id = :id', ['id' => $userId]);
            Session::remove('_2fa_setup_secret');
            ActivityLog::log($userId, '2fa_enabled', 'Two-factor authentication enabled.');
            Auth::refresh();
            Session::flash('success', 'Two-factor authentication enabled.');
            $this->redirect('profile');
        }

        if (!$user['two_fa_enabled']) {
            $secret = Totp::generateSecret();
            Session::set('_2fa_setup_secret', $secret);
            $uri = Totp::provisioningUri($secret, $user['email'], setting('site_name', '9JACASH'));
        }

        $this->view('user/two-factor-setup', [
            'title' => 'Two-Factor Authentication',
            'enabled' => (bool) $user['two_fa_enabled'],
            'secret' => $secret ?? null,
            'uri' => $uri ?? null,
        ], 'dashboard');
    }

    public function disableTwoFactor(): void
    {
        $this->verifyCsrf();
        $userId = (int) current_user()['id'];
        db()->update('users', ['two_fa_enabled' => 0, 'two_fa_secret' => null], 'id = :id', ['id' => $userId]);
        ActivityLog::log($userId, '2fa_disabled', 'Two-factor authentication disabled.');
        Auth::refresh();
        Session::flash('success', 'Two-factor authentication disabled.');
        $this->redirect('profile');
    }
}
