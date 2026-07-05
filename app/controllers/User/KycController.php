<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Upload;
use App\Models\ActivityLog;
use App\Models\KycSubmission;
use Exception;

class KycController extends Controller
{
    public function index(): void
    {
        $userId = (int) current_user()['id'];

        if ($this->isPost()) {
            $this->verifyCsrf();
            try {
                if (empty($_FILES['document']) || $_FILES['document']['error'] === UPLOAD_ERR_NO_FILE) {
                    throw new Exception('Please upload your identification document.');
                }
                $docPath = Upload::document($_FILES['document'], 'kyc');
                $selfiePath = null;
                if (!empty($_FILES['selfie']) && $_FILES['selfie']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $selfiePath = Upload::image($_FILES['selfie'], 'kyc');
                }

                KycSubmission::create([
                    'user_id' => $userId,
                    'document_type' => sanitize($this->post('document_type')),
                    'document_path' => $docPath,
                    'selfie_path' => $selfiePath,
                    'status' => 'pending',
                ]);

                db()->update('users', ['kyc_status' => 'pending'], 'id = :id', ['id' => $userId]);
                ActivityLog::log($userId, 'kyc_submitted', 'KYC documents submitted for review.');
                Session::flash('success', 'Your KYC documents have been submitted for review.');
            } catch (Exception $e) {
                Session::flash('error', $e->getMessage());
            }
            $this->redirect('kyc');
        }

        $latest = KycSubmission::latestForUser($userId);
        $this->view('user/kyc', ['title' => 'KYC Verification', 'latest' => $latest], 'dashboard');
    }
}
