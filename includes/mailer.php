<?php
/**
 * Mailer wrapper around PHPMailer with simple HTML templates for
 * every transactional email the platform sends.
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

final class Mailer
{
    private static function siteName(): string
    {
        return (string) get_setting('site_name', 'SURECASH MINING');
    }

    private static function build(): PHPMailer
    {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = get_setting('mail_host', env('MAIL_HOST', 'localhost'));
        $mail->SMTPAuth   = true;
        $mail->Username   = get_setting('mail_username', env('MAIL_USERNAME', ''));
        $mail->Password   = get_setting('mail_password', env('MAIL_PASSWORD', ''));
        $encryption       = get_setting('mail_encryption', env('MAIL_ENCRYPTION', 'tls'));
        $mail->SMTPSecure = $encryption === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) get_setting('mail_port', env('MAIL_PORT', 587));

        $mail->setFrom(
            (string) get_setting('mail_from_address', env('MAIL_FROM_ADDRESS', 'no-reply@surecashmining.com')),
            (string) get_setting('mail_from_name', env('MAIL_FROM_NAME', self::siteName()))
        );

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        // PHPMailer defaults to a 300-second connection timeout, which
        // would leave a request (e.g. a user login, which now sends a
        // notification email on every call) hanging for up to 5 minutes
        // if SMTP is slow, misconfigured, or unreachable. Bound it low
        // enough that a real SMTP handshake still completes comfortably,
        // but a dead/misconfigured host fails fast instead of blocking
        // the page that triggered the email.
        $mail->Timeout = 10;

        return $mail;
    }

    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        try {
            $mail = self::build();
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = $subject;
            $mail->Body    = self::wrapTemplate($subject, $htmlBody);
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (PHPMailerException|Throwable $e) {
            app_log('error', 'Mail send failed: ' . $e->getMessage(), ['to' => $toEmail]);
            return false;
        }
    }

    private static function wrapTemplate(string $title, string $content): string
    {
        $siteName = e(self::siteName());
        $safeTitle = e($title);
        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>{$safeTitle}</title></head>
<body style="margin:0;padding:0;background:#f2f4f7;font-family:'Segoe UI',Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f2f4f7;padding:30px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.06);">
          <tr>
            <td style="background:linear-gradient(135deg,#0B2545 0%,#0F5132 100%);padding:28px 32px;">
              <span style="font-size:22px;font-weight:700;color:#F2C94C;letter-spacing:1px;">{$siteName}</span>
            </td>
          </tr>
          <tr>
            <td style="padding:32px;color:#1f2937;font-size:15px;line-height:1.7;">
              {$content}
            </td>
          </tr>
          <tr>
            <td style="padding:20px 32px;background:#f8f9fb;color:#8a94a6;font-size:12px;text-align:center;">
              &copy; {$year} {$siteName}. All rights reserved.<br>
              This is an automated message, please do not reply directly to this email.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    public static function sendVerificationEmail(string $email, string $name, string $link): bool
    {
        $safeName = e($name);
        $safeLink = e($link);
        $safeSiteName = e(self::siteName());
        $body = "
            <h2 style='margin-top:0;color:#0B2545;'>Verify your email address</h2>
            <p>Hi {$safeName},</p>
            <p>Thanks for joining {$safeSiteName}! Please confirm this is your email address by clicking the button below.</p>
            <p style='text-align:center;margin:28px 0;'>
              <a href='{$safeLink}' style='background:#0F5132;color:#ffffff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block;'>Verify Email Address</a>
            </p>
            <p>Or copy and paste this link into your browser:<br><a href='{$safeLink}'>{$safeLink}</a></p>
            <p>This link expires in 24 hours. If you did not create this account, you can safely ignore this email.</p>
        ";

        return self::send($email, $name, 'Verify your ' . self::siteName() . ' email address', $body);
    }

    public static function sendWelcomeEmail(string $email, string $name): bool
    {
        $safeName = e($name);
        $safeSiteName = e(self::siteName());
        $body = "
            <h2 style='margin-top:0;color:#0B2545;'>Welcome to {$safeSiteName}, {$safeName}!</h2>
            <p>Your account has been verified and is now fully active. Start mining, completing tasks, and earning today.</p>
        ";

        return self::send($email, $name, 'Welcome to ' . self::siteName(), $body);
    }

    public static function sendPasswordResetEmail(string $email, string $name, string $link): bool
    {
        $safeName = e($name);
        $safeLink = e($link);
        $safeSiteName = e(self::siteName());
        $body = "
            <h2 style='margin-top:0;color:#0B2545;'>Reset your password</h2>
            <p>Hi {$safeName},</p>
            <p>We received a request to reset your {$safeSiteName} password. Click the button below to choose a new one.</p>
            <p style='text-align:center;margin:28px 0;'>
              <a href='{$safeLink}' style='background:#0F5132;color:#ffffff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block;'>Reset Password</a>
            </p>
            <p>Or copy and paste this link into your browser:<br><a href='{$safeLink}'>{$safeLink}</a></p>
            <p>This link expires in 1 hour. If you did not request a password reset, please ignore this email — your password will remain unchanged.</p>
        ";

        return self::send($email, $name, 'Reset your ' . self::siteName() . ' password', $body);
    }

    public static function sendPasswordChangedEmail(string $email, string $name): bool
    {
        $safeName = e($name);
        $safeSiteName = e(self::siteName());
        $body = "
            <h2 style='margin-top:0;color:#0B2545;'>Your password was changed</h2>
            <p>Hi {$safeName},</p>
            <p>This is a confirmation that your {$safeSiteName} account password was just changed. If this wasn't you, please contact support immediately.</p>
        ";

        return self::send($email, $name, 'Your ' . self::siteName() . ' password was changed', $body);
    }

    public static function sendDepositEmail(string $email, string $name, float $amount, string $status): bool
    {
        $safeName = e($name);
        $safeStatus = e($status);
        $body = "
            <h2 style='margin-top:0;color:#0B2545;'>Deposit {$safeStatus}</h2>
            <p>Hi {$safeName},</p>
            <p>Your deposit of <strong>" . money($amount) . "</strong> has been <strong>{$safeStatus}</strong>.</p>
        ";

        return self::send($email, $name, "Deposit {$status} - " . self::siteName(), $body);
    }

    public static function sendWithdrawalEmail(string $email, string $name, float $amount, string $status): bool
    {
        $safeName = e($name);
        $safeStatus = e($status);
        $body = "
            <h2 style='margin-top:0;color:#0B2545;'>Withdrawal {$safeStatus}</h2>
            <p>Hi {$safeName},</p>
            <p>Your withdrawal request of <strong>" . money($amount) . "</strong> has been <strong>{$safeStatus}</strong>.</p>
        ";

        return self::send($email, $name, "Withdrawal {$status} - " . self::siteName(), $body);
    }

    public static function sendLoginNotificationEmail(string $email, string $name, string $ipAddress, string $userAgent = ''): bool
    {
        $safeName = e($name);
        $safeIp = e($ipAddress);
        $safeDevice = e($userAgent !== '' ? $userAgent : 'Unknown device');
        $safeWhen = e(date('F j, Y \a\t g:i A T'));
        $body = "
            <h2 style='margin-top:0;color:#0B2545;'>New login to your account</h2>
            <p>Hi {$safeName},</p>
            <p>We noticed a login to your account on <strong>{$safeWhen}</strong>.</p>
            <table role='presentation' cellpadding='0' cellspacing='0' style='width:100%;margin:16px 0;font-size:14px;'>
              <tr><td style='color:#8a94a6;padding:4px 0;'>IP Address</td><td style='padding:4px 0;'>{$safeIp}</td></tr>
              <tr><td style='color:#8a94a6;padding:4px 0;'>Device / Browser</td><td style='padding:4px 0;'>{$safeDevice}</td></tr>
            </table>
            <p>If this was you, no action is needed. If you don't recognize this login, please change your password immediately and contact support.</p>
        ";

        return self::send($email, $name, 'New login to your ' . self::siteName() . ' account', $body);
    }
}
