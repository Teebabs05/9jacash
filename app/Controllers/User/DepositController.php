<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Upload;
use App\Models\ActivityLog;
use App\Models\Deposit;
use App\Models\Notification;
use App\Models\PaymentMethod;
use App\Services\DepositService;
use App\Services\PayVesselService;
use Exception;

class DepositController extends Controller
{
    public function index(): void
    {
        $userId = (int) current_user()['id'];
        $this->view('user/deposit', [
            'title' => 'Deposit',
            'paymentMethods' => PaymentMethod::active(),
            'recent' => Deposit::paginated(1, 5, null, $userId)['rows'],
            'minDeposit' => (float) setting('min_deposit', 500),
            'maxDeposit' => (float) setting('max_deposit', 1000000),
        ], 'dashboard');
    }

    public function manualStore(): void
    {
        $this->verifyCsrf();
        $userId = (int) current_user()['id'];
        $amount = (float) $this->post('amount');
        $paymentMethodId = (int) $this->post('payment_method_id');

        try {
            $min = (float) setting('min_deposit', 500);
            $max = (float) setting('max_deposit', 1000000);

            if ($amount < $min || $amount > $max) {
                throw new Exception("Deposit amount must be between " . money($min) . " and " . money($max) . ".");
            }
            if (empty($_FILES['receipt']) || $_FILES['receipt']['error'] === UPLOAD_ERR_NO_FILE) {
                throw new Exception('Please upload your payment receipt.');
            }

            $receiptPath = Upload::image($_FILES['receipt'], 'receipts', 4 * 1024 * 1024);

            Deposit::create([
                'user_id' => $userId,
                'amount' => $amount,
                'method' => 'manual',
                'reference' => generate_reference('DEP'),
                'payment_method_id' => $paymentMethodId,
                'receipt_path' => $receiptPath,
                'status' => 'pending',
            ]);

            ActivityLog::log($userId, 'deposit_submitted', 'Manual deposit of ' . money($amount) . ' submitted.');
            Session::flash('success', 'Your deposit has been submitted and is pending admin approval.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('deposit');
    }

    public function payvesselInit(): void
    {
        $this->verifyCsrf();
        $user = current_user();
        $amount = (float) $this->post('amount');
        $min = (float) setting('min_deposit', 500);
        $max = (float) setting('max_deposit', 1000000);

        if ($amount < $min || $amount > $max) {
            Session::flash('error', "Deposit amount must be between " . money($min) . " and " . money($max) . ".");
            $this->redirect('deposit');
        }

        $reference = generate_reference('PV');
        Deposit::create([
            'user_id' => $user['id'],
            'amount' => $amount,
            'method' => 'payvessel',
            'reference' => $reference,
            'status' => 'pending',
        ]);

        $result = PayVesselService::initializePayment($user, $amount, $reference);

        if (empty($result['status']) || empty($result['checkout_url'])) {
            Session::flash('error', $result['message'] ?? 'Unable to start payment. Please try manual deposit instead.');
            $this->redirect('deposit');
        }

        header('Location: ' . $result['checkout_url']);
        exit;
    }

    public function payvesselCallback(): void
    {
        $reference = (string) $this->input('reference', $this->input('trxref', ''));
        $deposit = $reference ? Deposit::findByReference($reference) : false;

        if ($deposit && $deposit['status'] === 'pending') {
            try {
                DepositService::confirmPayvesselDeposit($reference);
                Session::flash('success', 'Deposit confirmed! Your wallet has been credited.');
            } catch (Exception $e) {
                Session::flash('info', 'Your payment is being verified. Your wallet will be credited automatically once confirmed.');
            }
        } elseif ($deposit) {
            Session::flash('info', 'This deposit has already been processed.');
        } else {
            Session::flash('error', 'We could not find that transaction.');
        }

        $this->redirect('deposit/history');
    }

    public function history(): void
    {
        $userId = (int) current_user()['id'];
        $page = max(1, (int) $this->input('page', 1));
        $result = Deposit::paginated($page, 20, null, $userId);

        $this->view('user/deposit-history', [
            'title' => 'Deposit History',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => 20,
        ], 'dashboard');
    }
}
