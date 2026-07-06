<?php

declare(strict_types=1);

namespace App\Services;

/**
 * PayVessel collections integration.
 *
 * NOTE: PayVessel's exact endpoint paths/payload shape can change and are
 * gated behind merchant credentials, so verify them against the current
 * PayVessel API docs / sandbox before going live. This class isolates all
 * of that behind a small interface (initialize/verify/signature check) so
 * only this file needs updating if PayVessel changes their contract.
 */
class PayVesselService
{
    public static function initializePayment(array $user, float $amount, string $reference): array
    {
        $secretKey = config('payvessel.secret_key');
        $baseUrl = config('payvessel.base_url');

        if (!$secretKey) {
            return ['status' => false, 'message' => 'PayVessel is not configured. Please contact support or use manual deposit.'];
        }

        $payload = [
            'amount' => $amount,
            'reference' => $reference,
            'email' => $user['email'],
            'name' => $user['full_name'],
            'phone' => $user['phone'],
            'callback_url' => base_url('deposit/payvessel/callback'),
        ];

        $response = self::request('POST', '/api/v2/payment/initialize', $payload, $secretKey);

        if (!$response || empty($response['status'])) {
            return ['status' => false, 'message' => $response['message'] ?? 'Unable to initialize payment. Please try again.'];
        }

        return [
            'status' => true,
            'checkout_url' => $response['data']['checkout_url'] ?? $response['data']['authorization_url'] ?? null,
        ];
    }

    public static function verifyTransaction(string $reference): array
    {
        $secretKey = config('payvessel.secret_key');
        if (!$secretKey) {
            return ['status' => false];
        }

        $response = self::request('GET', '/api/v2/payment/verify/' . urlencode($reference), [], $secretKey);

        if (!$response || empty($response['status'])) {
            return ['status' => false];
        }

        $data = $response['data'] ?? [];
        return [
            'status' => ($data['status'] ?? '') === 'success',
            'amount' => (float) ($data['amount'] ?? 0),
            'reference' => $data['reference'] ?? $reference,
        ];
    }

    public static function verifyWebhookSignature(string $rawPayload, string $signature): bool
    {
        $secret = config('payvessel.webhook_secret');
        if (!$secret || !$signature) {
            return false;
        }
        $expected = hash_hmac('sha512', $rawPayload, $secret);
        return hash_equals($expected, $signature);
    }

    private static function request(string $method, string $path, array $payload, string $secretKey): ?array
    {
        $url = rtrim(config('payvessel.base_url'), '/') . $path;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $body = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            error_log('PayVessel request failed: ' . $error);
            return null;
        }

        $decoded = json_decode((string) $body, true);
        return is_array($decoded) ? $decoded : null;
    }
}
