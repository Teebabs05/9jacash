<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Upload;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Exception;

class SupportController extends Controller
{
    public function index(): void
    {
        $userId = (int) current_user()['id'];
        $this->view('user/support/index', [
            'title' => 'Support Tickets',
            'tickets' => SupportTicket::forUser($userId),
        ], 'dashboard');
    }

    public function create(): void
    {
        if ($this->isPost()) {
            $this->verifyCsrf();
            $userId = (int) current_user()['id'];

            try {
                $attachment = null;
                if (!empty($_FILES['attachment']['name'])) {
                    $attachment = Upload::image($_FILES['attachment'], 'proofs');
                }

                $ticketId = SupportTicket::create([
                    'user_id' => $userId,
                    'subject' => sanitize($this->post('subject')),
                    'category' => sanitize($this->post('category', 'general')),
                    'priority' => sanitize($this->post('priority', 'medium')),
                    'status' => 'open',
                ]);

                SupportMessage::create([
                    'ticket_id' => $ticketId,
                    'sender_type' => 'user',
                    'sender_id' => $userId,
                    'message' => sanitize($this->post('message')),
                    'attachment' => $attachment,
                ]);

                Session::flash('success', 'Support ticket created. Our team will respond shortly.');
                $this->redirect('support/' . $ticketId);
            } catch (Exception $e) {
                Session::flash('error', $e->getMessage());
                $this->redirect('support/new');
            }
        }

        $this->view('user/support/create', ['title' => 'New Ticket'], 'dashboard');
    }

    public function show(int $id): void
    {
        $userId = (int) current_user()['id'];
        $ticket = SupportTicket::find($id);
        if (!$ticket || (int) $ticket['user_id'] !== $userId) {
            Session::flash('error', 'Ticket not found.');
            $this->redirect('support');
        }

        $this->view('user/support/show', [
            'title' => 'Ticket #' . $id,
            'ticket' => $ticket,
            'messages' => SupportMessage::forTicket($id),
        ], 'dashboard');
    }

    public function reply(int $id): void
    {
        $this->verifyCsrf();
        $userId = (int) current_user()['id'];
        $ticket = SupportTicket::find($id);

        if (!$ticket || (int) $ticket['user_id'] !== $userId) {
            Session::flash('error', 'Ticket not found.');
            $this->redirect('support');
        }

        $attachment = null;
        if (!empty($_FILES['attachment']['name'])) {
            $attachment = Upload::image($_FILES['attachment'], 'proofs');
        }

        SupportMessage::create([
            'ticket_id' => $id,
            'sender_type' => 'user',
            'sender_id' => $userId,
            'message' => sanitize($this->post('message')),
            'attachment' => $attachment,
        ]);
        SupportTicket::updateById($id, ['status' => 'open']);

        $this->redirect('support/' . $id);
    }

    public function close(int $id): void
    {
        $this->verifyCsrf();
        $userId = (int) current_user()['id'];
        $ticket = SupportTicket::find($id);
        if ($ticket && (int) $ticket['user_id'] === $userId) {
            SupportTicket::updateById($id, ['status' => 'closed']);
            Session::flash('success', 'Ticket closed.');
        }
        $this->redirect('support/' . $id);
    }
}
