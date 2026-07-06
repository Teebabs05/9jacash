<?php

declare(strict_types=1);

namespace App\Core;

use Exception;

/**
 * Dependency-free SMTP mailer (no Composer/PHPMailer requirement so the
 * app can be dropped onto shared hosting as-is). Falls back to PHP's
 * mail() if SMTP host is not configured.
 */
class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        $config = App::config('mail');

        if (empty($config['host'])) {
            $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: {$config['from_name']} <{$config['from_address']}>\r\n";
            return @mail($to, $subject, $htmlBody, $headers);
        }

        try {
            return self::sendViaSmtp($config, $to, $subject, $htmlBody);
        } catch (Exception $e) {
            error_log('Mailer SMTP error: ' . $e->getMessage());
            return false;
        }
    }

    private static function sendViaSmtp(array $config, string $to, string $subject, string $htmlBody): bool
    {
        $host = $config['encryption'] === 'ssl' ? 'ssl://' . $config['host'] : $config['host'];
        $socket = @stream_socket_client(
            "{$host}:{$config['port']}",
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            throw new Exception("SMTP connect failed: {$errstr}");
        }

        $read = fn() => fgets($socket, 512);
        $write = function (string $cmd) use ($socket): void {
            fwrite($socket, $cmd . "\r\n");
        };
        $expect = function (string $expectedCode) use ($read): string {
            $response = '';
            do {
                $line = $read();
                $response .= $line;
            } while (isset($line[3]) && $line[3] === '-');
            if (!str_starts_with($response, $expectedCode)) {
                throw new Exception("SMTP unexpected response: {$response}");
            }
            return $response;
        };

        $expect('220');
        $write('EHLO 9jacash');
        $expect('250');

        if ($config['encryption'] === 'tls') {
            $write('STARTTLS');
            $expect('220');
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $write('EHLO 9jacash');
            $expect('250');
        }

        $write('AUTH LOGIN');
        $expect('334');
        $write(base64_encode($config['username']));
        $expect('334');
        $write(base64_encode($config['password']));
        $expect('235');

        $write("MAIL FROM:<{$config['from_address']}>");
        $expect('250');
        $write("RCPT TO:<{$to}>");
        $expect('250');
        $write('DATA');
        $expect('354');

        $headers = "From: {$config['from_name']} <{$config['from_address']}>\r\n";
        $headers .= "To: <{$to}>\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $body = str_replace("\n.", "\n..", $htmlBody);
        $write($headers . "\r\n" . $body . "\r\n.");
        $expect('250');
        $write('QUIT');
        fclose($socket);

        return true;
    }

    public static function template(string $title, string $bodyHtml): string
    {
        $appName = e(config('app.name'));
        $year = date('Y');
        return <<<HTML
        <div style="font-family:Arial,sans-serif;background:#f4f6fb;padding:30px;">
          <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.06);">
            <div style="background:linear-gradient(135deg,#0D47A1,#1565C0);padding:24px;text-align:center;">
              <h1 style="color:#fff;margin:0;font-size:22px;letter-spacing:1px;">{$appName}</h1>
            </div>
            <div style="padding:28px;color:#333;">
              <h2 style="font-size:18px;color:#0D47A1;">{$title}</h2>
              {$bodyHtml}
            </div>
            <div style="padding:16px;text-align:center;font-size:12px;color:#999;background:#fafafa;">
              &copy; {$year} {$appName}. All rights reserved.
            </div>
          </div>
        </div>
        HTML;
    }
}
