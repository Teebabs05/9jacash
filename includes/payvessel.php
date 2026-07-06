<?php
/**
 * PayVessel payment gateway client.
 *
 * Reference: https://payvessel.gitbook.io/payvessel/
 * (Create Virtual Account + Payment Notification/webhook pages).
 *
 * Generates a one-time ("DYNAMIC") reserved account number for a single
 * deposit. The user transfers into that account; PayVessel settles the
 * transaction and calls our webhook (api/payvessel-webhook.php), which
 * verifies the HMAC-SHA512 signature before crediting the wallet.
 */

declare(strict_types=1);

final class PayVessel
{
    public static function isConfigured(): bool
    {
        return self::publicKey() !== '' && self::secretKey() !== '' && self::businessId() !== '';
    }

    private static function publicKey(): string
    {
        return (string) get_setting('payvessel_public_key', env('PAYVESSEL_PUBLIC_KEY', ''));
    }

    private static function secretKey(): string
    {
        return (string) get_setting('payvessel_secret_key', env('PAYVESSEL_SECRET_KEY', ''));
    }

    private static function businessId(): string
    {
        return (string) get_setting('payvessel_business_id', env('PAYVESSEL_BUSINESS_ID', ''));
    }

    private static function baseUrl(): string
    {
        return rtrim((string) get_setting('payvessel_base_url', env('PAYVESSEL_BASE_URL', 'https://api.payvessel.com')), '/');
    }

    private static function bankCodes(): array
    {
        $raw = (string) get_setting('payvessel_bank_codes', env('PAYVESSEL_BANK_CODES', '120001'));
        $codes = array_filter(array_map('trim', explode(',', $raw)));
        return $codes ?: ['120001'];
    }

    /**
     * Request a one-time reserved account number for a deposit.
     *
     * @return array{success:bool,message:string,data?:array}
     */
    public static function createVirtualAccount(string $email, string $fullName, string $phone): array
    {
        if (!self::isConfigured()) {
            return [
                'success' => false,
                'message' => 'Automatic bank transfer is not configured yet. Please use manual deposit or contact support.',
            ];
        }

        $payload = [
            'email' => $email,
            'name' => $fullName,
            'phoneNumber' => $phone,
            'bankcode' => self::bankCodes(),
            'account_type' => 'DYNAMIC',
            'businessid' => self::businessId(),
            'bvn' => '',
        ];

        $response = self::request('POST', '/pms/api/external/request/customerReservedAccount/', $payload);

        if (!$response['success']) {
            return $response;
        }

        $body = $response['data'];
        $bank = $body['banks'][0] ?? null;

        if (!$bank || empty($bank['accountNumber']) || empty($bank['trackingReference'])) {
            app_log('error', 'PayVessel: unexpected create-account response shape', ['response' => $body]);
            return ['success' => false, 'message' => 'Could not generate a deposit account right now. Please try again shortly.'];
        }

        return [
            'success' => true,
            'message' => 'Virtual account generated.',
            'data' => [
                'bank_name' => $bank['bankName'] ?? '',
                'account_number' => $bank['accountNumber'],
                'account_name' => $bank['accountName'] ?? $fullName,
                'tracking_reference' => $bank['trackingReference'],
                'expires_at' => $bank['expire_date'] ?? null,
                'raw' => $body,
            ],
        ];
    }

    /**
     * Verify a webhook's HMAC-SHA512 signature against the raw request body.
     */
    public static function verifyWebhookSignature(string $rawPayload, ?string $signatureHeader): bool
    {
        if (!$signatureHeader || self::secretKey() === '') {
            return false;
        }

        $expected = hash_hmac('sha512', $rawPayload, self::secretKey());

        return hash_equals($expected, $signatureHeader);
    }

    /**
     * PayVessel's documented trusted webhook source IPs. Used as a
     * defense-in-depth signal only — a mismatch is logged but does not
     * block processing, since shared hosting behind a proxy/CDN often
     * cannot see the true origin IP. The HMAC signature is the real
     * authenticity guarantee.
     */
    public static function isTrustedIp(string $ip): bool
    {
        return in_array($ip, ['3.255.23.38', '162.246.254.36'], true);
    }

    private static function request(string $method, string $path, array $payload): array
    {
        $url = self::baseUrl() . $path;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'api-key: ' . self::publicKey(),
                'api-secret: Bearer ' . self::secretKey(),
            ],
        ]);

        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            app_log('error', 'PayVessel request failed: ' . $error, ['path' => $path]);
            return ['success' => false, 'message' => 'Could not reach the payment gateway. Please try again shortly.'];
        }

        $decoded = json_decode($raw, true);

        if ($httpCode >= 400 || !is_array($decoded)) {
            app_log('error', 'PayVessel returned an error response', ['http_code' => $httpCode, 'body' => $raw]);
            return ['success' => false, 'message' => 'The payment gateway rejected the request. Please try again shortly.'];
        }

        return ['success' => true, 'data' => $decoded];
    }
}
