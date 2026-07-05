<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\KycSubmission;
use App\Models\Notification;

class KycController extends Controller
{
    public function index(): void
    {
        $page = max(1, (int) $this->input('page', 1));
        $status = $this->input('status') ?: null;
        $result = KycSubmission::paginatedPending($page, 20, $status);

        $this->view('admin/kyc/index', [
            'title' => 'KYC Requests',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => 20,
            'status' => $status,
        ], 'admin');
    }

    public function approve(int $id): void
    {
        $this->verifyCsrf();
        $submission = KycSubmission::find($id);
        if ($submission) {
            KycSubmission::updateById($id, ['status' => 'approved']);
            db()->update('users', ['kyc_status' => 'approved'], 'id = :id', ['id' => $submission['user_id']]);
            Notification::send((int) $submission['user_id'], 'KYC Approved', 'Your identity verification has been approved.', 'success');
        }
        Session::flash('success', 'KYC approved.');
        $this->redirect('admin/kyc');
    }

    public function reject(int $id): void
    {
        $this->verifyCsrf();
        $note = sanitize($this->post('note', 'Documents unclear or invalid.'));
        $submission = KycSubmission::find($id);
        if ($submission) {
            KycSubmission::updateById($id, ['status' => 'rejected', 'admin_note' => $note]);
            db()->update('users', ['kyc_status' => 'rejected'], 'id = :id', ['id' => $submission['user_id']]);
            Notification::send((int) $submission['user_id'], 'KYC Rejected', 'Your identity verification was rejected: ' . $note, 'error');
        }
        Session::flash('success', 'KYC rejected.');
        $this->redirect('admin/kyc');
    }
}
