<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Upload;
use App\Models\Notification;
use App\Models\SupportMessage;
use App\Models\SupportTicket;

class SupportController extends Controller
{
    public function index(): void
    {
        $page = max(1, (int) $this->input('page', 1));
        $status = $this->input('status') ?: null;
        $result = SupportTicket::paginated($page, 20, $status);

        $this->view('admin/support/index', [
            'title' => 'Support Tickets',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => 20,
            'status' => $status,
        ], 'admin');
    }

    public function show(int $id): void
    {
        $ticket = SupportTicket::find($id);
        if (!$ticket) {
            Session::flash('error', 'Ticket not found.');
            $this->redirect('admin/support');
        }

        $this->view('admin/support/show', [
            'title' => 'Ticket #' . $id,
            'ticket' => $ticket,
            'messages' => SupportMessage::forTicket($id),
        ], 'admin');
    }

    public function reply(int $id): void
    {
        $this->verifyCsrf();
        $ticket = SupportTicket::find($id);
        if (!$ticket) {
            Session::flash('error', 'Ticket not found.');
            $this->redirect('admin/support');
        }

        $attachment = null;
        if (!empty($_FILES['attachment']['name'])) {
            $attachment = Upload::image($_FILES['attachment'], 'proofs');
        }

        SupportMessage::create([
            'ticket_id' => $id,
            'sender_type' => 'admin',
            'sender_id' => (int) current_user()['id'],
            'message' => sanitize($this->post('message')),
            'attachment' => $attachment,
        ]);
        SupportTicket::updateById($id, ['status' => 'answered']);
        Notification::send((int) $ticket['user_id'], 'Support Reply', 'Our support team replied to your ticket: ' . $ticket['subject'], 'info');

        $this->redirect('admin/support/' . $id);
    }

    public function close(int $id): void
    {
        $this->verifyCsrf();
        SupportTicket::updateById($id, ['status' => 'closed']);
        Session::flash('success', 'Ticket closed.');
        $this->redirect('admin/support/' . $id);
    }
}
