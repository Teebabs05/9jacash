<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Deposit;
use App\Services\DepositService;
use App\Services\PayVesselService;
use Exception;

class PayVesselController extends Controller
{
    /**
     * PayVessel calls this URL server-to-server on payment completion.
     * The exact signature header name should be confirmed against
     * PayVessel's docs — adjust the header key below if needed.
     */
    public function webhook(): void
    {
        $rawPayload = file_get_contents('php://input') ?: '';
        $signature = $_SERVER['HTTP_X_PAYVESSEL_SIGNATURE'] ?? $_SERVER['HTTP_X_SIGNATURE'] ?? '';

        $valid = PayVesselService::verifyWebhookSignature($rawPayload, $signature);
        $payload = json_decode($rawPayload, true) ?: [];
        $reference = $payload['reference'] ?? $payload['data']['reference'] ?? null;

        db()->insert('webhook_logs', [
            'provider' => 'payvessel',
            'reference' => $reference,
            'payload' => substr($rawPayload, 0, 60000),
            'signature_valid' => $valid ? 1 : 0,
            'status' => 'received',
        ]);

        if (!$valid) {
            http_response_code(401);
            echo json_encode(['status' => false, 'message' => 'Invalid signature']);
            return;
        }

        if (!$reference) {
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Missing reference']);
            return;
        }

        try {
            $deposit = Deposit::findByReference($reference);
            if ($deposit && $deposit['status'] === 'pending') {
                DepositService::confirmPayvesselDeposit($reference);
            }
            echo json_encode(['status' => true]);
        } catch (Exception $e) {
            error_log('PayVessel webhook processing error: ' . $e->getMessage());
            http_response_code(200); // acknowledge receipt so the gateway doesn't hammer retries
            echo json_encode(['status' => false, 'message' => 'Deferred']);
        }
    }
}
