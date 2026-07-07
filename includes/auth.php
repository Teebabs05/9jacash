<?php
/**
 * Authentication service: registration, login, logout, remember-me,
 * email verification and password reset for regular users.
 */

declare(strict_types=1);

final class Auth
{
    // -----------------------------------------------------------
    // Registration
    // -----------------------------------------------------------
    public static function register(string $fullName, string $username, string $email, string $phone, string $password, ?string $referralCode): array
    {
        $pdo = db();

        $username = strtolower(trim($username));
        $email    = strtolower(trim($email));

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'An account with that email or username already exists.'];
        }

        $referredBy = null;
        if ($referralCode) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE referral_code = ? LIMIT 1');
            $stmt->execute([strtoupper(trim($referralCode))]);
            $referrer = $stmt->fetch();
            $referredBy = $referrer['id'] ?? null;
        }

        $referralCodeNew = generate_referral_code($username);
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, username, email, phone, password, referral_code, referred_by, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([$fullName, $username, $email, $phone, $hashedPassword, $referralCodeNew, $referredBy, USER_STATUS_ACTIVE]);
            $userId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO wallets (user_id, main_balance, bonus_balance, referral_balance, mining_balance, pending_balance) VALUES (?, 0, 0, 0, 0, 0)');
            $stmt->execute([$userId]);

            if ($referredBy) {
                self::buildReferralChain($userId, (int) $referredBy);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            app_log('error', 'Registration failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }

        $registrationBonus = (float) get_setting('registration_bonus', 0);
        if ($registrationBonus > 0) {
            try {
                wallet_credit($userId, WALLET_BONUS, $registrationBonus, LEDGER_SOURCE_ADMIN_ADJUSTMENT, 'Welcome registration bonus');
            } catch (Throwable $e) {
                app_log('error', 'Failed to credit registration bonus: ' . $e->getMessage(), ['user_id' => $userId]);
            }
        }

        self::sendVerificationEmail($userId, $email, $fullName);
        log_activity($userId, null, 'register', 'New account registered');

        return ['success' => true, 'message' => 'Account created successfully.', 'user_id' => $userId];
    }

    /**
     * Record the multi-level referral chain (up to 10 levels) for a new user.
     */
    private static function buildReferralChain(int $newUserId, int $directReferrerId): void
    {
        $pdo = db();
        $maxLevels = (int) get_setting('referral_max_levels', 3);

        $currentReferrerId = $directReferrerId;
        $level = 1;

        $stmt = $pdo->prepare('INSERT INTO referrals (user_id, referred_id, level, created_at) VALUES (?, ?, ?, NOW())');

        while ($currentReferrerId && $level <= $maxLevels) {
            $stmt->execute([$currentReferrerId, $newUserId, $level]);

            $next = $pdo->prepare('SELECT referred_by FROM users WHERE id = ? LIMIT 1');
            $next->execute([$currentReferrerId]);
            $row = $next->fetch();

            $currentReferrerId = $row['referred_by'] ?? null;
            $level++;
        }
    }

    // -----------------------------------------------------------
    // Login / Logout
    // -----------------------------------------------------------
    public static function attemptLogin(string $login, string $password, bool $remember = false): array
    {
        $pdo = db();
        $identifier = strtolower(trim($login)) . ':' . client_ip();

        if (is_rate_limited($identifier)) {
            return ['success' => false, 'message' => 'Too many failed attempts. Please try again in 15 minutes.'];
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
        $stmt->execute([strtolower(trim($login)), strtolower(trim($login))]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            register_failed_attempt($identifier);
            log_activity($user['id'] ?? null, null, 'login_failed', "Failed login for '{$login}'");
            return ['success' => false, 'message' => 'Invalid credentials. Please check your email/username and password.'];
        }

        if ($user['status'] === USER_STATUS_SUSPENDED) {
            return ['success' => false, 'message' => 'Your account has been suspended. Contact support for assistance.'];
        }

        if ($user['status'] === USER_STATUS_BANNED) {
            return ['success' => false, 'message' => 'Your account has been permanently banned.'];
        }

        if (empty($user['email_verified_at'])) {
            return ['success' => false, 'message' => 'Please verify your email address before logging in.', 'unverified' => true, 'user_id' => $user['id']];
        }

        clear_failed_attempts($identifier);
        self::establishSession($user);

        if ($remember) {
            self::setRememberCookie((int) $user['id']);
        }

        $stmt = $pdo->prepare('UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?');
        $stmt->execute([client_ip(), $user['id']]);

        log_activity((int) $user['id'], null, 'login', 'User logged in');
        defer_after_response(fn () => Mailer::sendLoginNotificationEmail($user['email'], $user['full_name'], client_ip(), (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')));

        return ['success' => true, 'message' => 'Welcome back!'];
    }

    /**
     * Complete a login already authenticated by another factor (biometric
     * WebAuthn assertion) - same session/notification/logging tail as
     * attemptLogin(), skipping the password check it doesn't need.
     */
    public static function completeBiometricLogin(int $userId): array
    {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Account not found.'];
        }

        if ($user['status'] === USER_STATUS_SUSPENDED) {
            return ['success' => false, 'message' => 'Your account has been suspended. Contact support for assistance.'];
        }

        if ($user['status'] === USER_STATUS_BANNED) {
            return ['success' => false, 'message' => 'Your account has been permanently banned.'];
        }

        self::establishSession($user);

        $pdo->prepare('UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?')
            ->execute([client_ip(), $user['id']]);

        log_activity((int) $user['id'], null, 'login', 'User logged in with biometrics');
        defer_after_response(fn () => Mailer::sendLoginNotificationEmail($user['email'], $user['full_name'], client_ip(), (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')));

        return ['success' => true, 'message' => 'Welcome back!'];
    }

    private static function establishSession(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['_last_regen'] = time();
    }

    public static function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;

        if ($userId) {
            log_activity((int) $userId, null, 'logout', 'User logged out');

            $stmt = db()->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
            $stmt->execute([$userId]);
        }

        if (!empty($_COOKIE['remember_me'])) {
            setcookie('remember_me', '', time() - 3600, '/', '', false, true);
        }

        $_SESSION = [];
        session_destroy();
    }

    public static function isLoggedIn(): bool
    {
        if (!empty($_SESSION['user_id'])) {
            $stmt = db()->prepare('SELECT status FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$_SESSION['user_id']]);
            $row = $stmt->fetch();

            if ($row && $row['status'] === USER_STATUS_ACTIVE) {
                return true;
            }

            // Stale session: the account was deleted, or suspended/banned
            // after this session was established. Clearing it here (rather
            // than only checking at login time) means every page doesn't
            // have to guard against current_user() returning null for a
            // session that looks logged-in but points at a dead/blocked
            // account - previously this crashed get_wallet() with a
            // foreign key violation when $user['id'] silently became 0.
            unset($_SESSION['user_id'], $_SESSION['username']);
        }

        return self::loginFromRememberCookie();
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            redirect(rtrim(APP_URL, '/') . '/user/login.php');
        }
    }

    public static function requireGuest(): void
    {
        if (self::isLoggedIn()) {
            redirect(rtrim(APP_URL, '/') . '/user/dashboard.php');
        }
    }

    // -----------------------------------------------------------
    // Remember Me (selector/validator pattern)
    // -----------------------------------------------------------
    private static function setRememberCookie(int $userId): void
    {
        $selector = bin2hex(random_bytes(9));
        $validator = bin2hex(random_bytes(33));
        $expiresAt = date('Y-m-d H:i:s', time() + REMEMBER_ME_TTL);

        $stmt = db()->prepare(
            'INSERT INTO remember_tokens (user_id, selector, hashed_validator, expires_at, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$userId, $selector, hash('sha256', $validator), $expiresAt]);

        setcookie(
            'remember_me',
            $selector . ':' . $validator,
            [
                'expires'  => time() + REMEMBER_ME_TTL,
                'path'     => '/',
                'secure'   => (defined('APP_ENV') && APP_ENV === 'production'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    private static function loginFromRememberCookie(): bool
    {
        if (empty($_COOKIE['remember_me']) || !str_contains($_COOKIE['remember_me'], ':')) {
            return false;
        }

        [$selector, $validator] = explode(':', $_COOKIE['remember_me'], 2);

        $stmt = db()->prepare('SELECT * FROM remember_tokens WHERE selector = ? LIMIT 1');
        $stmt->execute([$selector]);
        $token = $stmt->fetch();

        if (!$token || strtotime((string) $token['expires_at']) < time()) {
            return false;
        }

        if (!hash_equals($token['hashed_validator'], hash('sha256', $validator))) {
            app_log('warning', 'Remember-me token mismatch (possible theft)', ['selector' => $selector]);
            return false;
        }

        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$token['user_id']]);
        $user = $stmt->fetch();

        if (!$user || $user['status'] !== USER_STATUS_ACTIVE) {
            return false;
        }

        self::establishSession($user);

        return true;
    }

    // -----------------------------------------------------------
    // Email verification
    // -----------------------------------------------------------
    public static function sendVerificationEmail(int $userId, string $email, string $fullName): void
    {
        $token = generate_token(32);
        $expiresAt = date('Y-m-d H:i:s', time() + EMAIL_VERIFICATION_TTL);

        $pdo = db();
        $pdo->prepare('DELETE FROM email_verifications WHERE user_id = ?')->execute([$userId]);
        $stmt = $pdo->prepare('INSERT INTO email_verifications (user_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$userId, hash('sha256', $token), $expiresAt]);

        $link = rtrim(APP_URL, '/') . '/user/verify-email.php?token=' . $token . '&uid=' . $userId;
        Mailer::sendVerificationEmail($email, $fullName, $link);
    }

    public static function verifyEmailToken(int $userId, string $token): array
    {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM email_verifications WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $record = $stmt->fetch();

        if (!$record || !hash_equals($record['token'], hash('sha256', $token))) {
            return ['success' => false, 'message' => 'Invalid or expired verification link.'];
        }

        if (strtotime((string) $record['expires_at']) < time()) {
            return ['success' => false, 'message' => 'This verification link has expired. Please request a new one.'];
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Account not found.'];
        }

        if (!empty($user['email_verified_at'])) {
            return ['success' => true, 'message' => 'Your email is already verified. You can log in now.'];
        }

        $pdo->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = ?')->execute([$userId]);
        $pdo->prepare('DELETE FROM email_verifications WHERE user_id = ?')->execute([$userId]);

        notify_user($userId, 'Email Verified', 'Your email address has been verified successfully.', NOTIFY_TYPE_SYSTEM);
        Mailer::sendWelcomeEmail($user['email'], $user['full_name']);
        log_activity($userId, null, 'email_verified', 'User verified their email address');

        return ['success' => true, 'message' => 'Your email has been verified. You can now log in.'];
    }

    // -----------------------------------------------------------
    // Password reset
    // -----------------------------------------------------------
    public static function sendPasswordReset(string $email): array
    {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch();

        // Always return a generic success message to avoid leaking which emails are registered.
        $generic = ['success' => true, 'message' => 'If an account exists for that email, a password reset link has been sent.'];

        if (!$user) {
            return $generic;
        }

        $token = generate_token(32);
        $expiresAt = date('Y-m-d H:i:s', time() + PASSWORD_RESET_TTL);

        $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);
        $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at, used, created_at) VALUES (?, ?, ?, 0, NOW())');
        $stmt->execute([$user['id'], hash('sha256', $token), $expiresAt]);

        $link = rtrim(APP_URL, '/') . '/user/reset-password.php?token=' . $token . '&uid=' . $user['id'];
        Mailer::sendPasswordResetEmail($user['email'], $user['full_name'], $link);

        log_activity((int) $user['id'], null, 'password_reset_requested', 'Password reset link requested');

        return $generic;
    }

    public static function resetPassword(int $userId, string $token, string $newPassword): array
    {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE user_id = ? AND used = 0 LIMIT 1');
        $stmt->execute([$userId]);
        $record = $stmt->fetch();

        if (!$record || !hash_equals($record['token'], hash('sha256', $token))) {
            return ['success' => false, 'message' => 'Invalid or expired password reset link.'];
        }

        if (strtotime((string) $record['expires_at']) < time()) {
            return ['success' => false, 'message' => 'This password reset link has expired. Please request a new one.'];
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Account not found.'];
        }

        $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $pdo->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?')->execute([$hashed, $userId]);
        $pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')->execute([$record['id']]);
        $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$userId]);

        Mailer::sendPasswordChangedEmail($user['email'], $user['full_name']);
        log_activity($userId, null, 'password_reset', 'Password was reset successfully');

        return ['success' => true, 'message' => 'Your password has been reset. You can now log in with your new password.'];
    }
}
