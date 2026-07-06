<?php
/**
 * Authentication service for the admin panel.
 */

declare(strict_types=1);

final class AdminAuth
{
    public static function attemptLogin(string $username, string $password): array
    {
        $pdo = db();
        $identifier = 'admin:' . strtolower(trim($username)) . ':' . client_ip();

        if (is_rate_limited($identifier)) {
            return ['success' => false, 'message' => 'Too many failed attempts. Please try again in 15 minutes.'];
        }

        $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([strtolower(trim($username)), strtolower(trim($username))]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password'])) {
            register_failed_attempt($identifier);
            return ['success' => false, 'message' => 'Invalid administrator credentials.'];
        }

        if ($admin['status'] !== 'active') {
            return ['success' => false, 'message' => 'This administrator account is disabled.'];
        }

        clear_failed_attempts($identifier);

        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];

        $pdo->prepare('UPDATE admins SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?')
            ->execute([client_ip(), $admin['id']]);

        log_activity(null, (int) $admin['id'], 'admin_login', 'Administrator logged in');
        Mailer::sendLoginNotificationEmail($admin['email'], $admin['full_name'], client_ip(), (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));

        return ['success' => true, 'message' => 'Welcome back!'];
    }

    /**
     * Complete a login already authenticated by another factor (biometric
     * WebAuthn assertion) - same session/notification/logging tail as
     * attemptLogin(), skipping the password check it doesn't need.
     */
    public static function completeBiometricLogin(int $adminId): array
    {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();

        if (!$admin) {
            return ['success' => false, 'message' => 'Administrator account not found.'];
        }

        if ($admin['status'] !== 'active') {
            return ['success' => false, 'message' => 'This administrator account is disabled.'];
        }

        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];

        $pdo->prepare('UPDATE admins SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?')
            ->execute([client_ip(), $admin['id']]);

        log_activity(null, (int) $admin['id'], 'admin_login', 'Administrator logged in with biometrics');
        Mailer::sendLoginNotificationEmail($admin['email'], $admin['full_name'], client_ip(), (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));

        return ['success' => true, 'message' => 'Welcome back!'];
    }

    public static function logout(): void
    {
        if (!empty($_SESSION['admin_id'])) {
            log_activity(null, (int) $_SESSION['admin_id'], 'admin_logout', 'Administrator logged out');
            unset($_SESSION['admin_id'], $_SESSION['admin_username']);
        }
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            redirect(rtrim(APP_URL, '/') . '/admin/login.php');
        }
    }

    public static function requireGuest(): void
    {
        if (self::isLoggedIn()) {
            redirect(rtrim(APP_URL, '/') . '/admin/index.php');
        }
    }
}
