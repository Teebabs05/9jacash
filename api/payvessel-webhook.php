<?php
/**
 * PayVessel payment notification webhook.
 *
 * Reference: https://payvessel.gitbook.io/payvessel/services/payment-notification
 * PayVessel signs the raw request body with HMAC-SHA512 using your
 * secret key and sends it in the `Payvessel-Http-Signature` header.
 * This endpoint is server-to-server: no session, no CSRF token.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
    exit;
}

$rawPayload = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_PAYVESSEL_HTTP_SIGNATURE'] ?? null;

if (!PayVessel::isTrustedIp(client_ip())) {
    app_log('warning', 'PayVessel webhook from untrusted IP', ['ip' => client_ip()]);
}

if (!PayVessel::verifyWebhookSignature($rawPayload, $signature)) {
    app_log('warning', 'PayVessel webhook signature verification failed', ['ip' => client_ip()]);
    http_response_code(400);
    echo json_encode(['message' => 'Permission denied, invalid hash.']);
    exit;
}

$data = json_decode($rawPayload, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid payload.']);
    exit;
}

$reference = $data['transaction']['reference'] ?? null;
$settlementAmount = (float) ($data['order']['settlement_amount'] ?? $data['order']['amount'] ?? 0);

if (!$reference || $settlementAmount <= 0) {
    http_response_code(400);
    echo json_encode(['message' => 'Missing transaction reference or amount.']);
    exit;
}

$result = deposits_handle_payvessel_notification($reference, $settlementAmount);

app_log('info', 'PayVessel webhook processed', ['reference' => $reference, 'amount' => $settlementAmount, 'result' => $result['message']]);

http_response_code(200);
echo json_encode(['message' => $result['message']]);
