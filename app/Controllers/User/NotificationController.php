<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Controller;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index(): void
    {
        $userId = (int) current_user()['id'];
        $notifications = Notification::forUser($userId, 50);
        Notification::markAllRead($userId);

        $this->view('user/notifications', [
            'title' => 'Notifications',
            'notifications' => $notifications,
        ], 'dashboard');
    }

    public function markAllRead(): void
    {
        $this->verifyCsrf();
        Notification::markAllRead((int) current_user()['id']);
        $this->json(['success' => true]);
    }
}
