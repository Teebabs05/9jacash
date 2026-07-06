<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;

class DashboardController extends Controller
{
    public function index(): void
    {
        $db = db();

        $stats = [
            'total_users' => (int) $db->fetch("SELECT COUNT(*) c FROM users WHERE role = 'user'")['c'],
            'active_users' => (int) $db->fetch("SELECT COUNT(*) c FROM users WHERE role = 'user' AND status = 'active'")['c'],
            'pending_users' => (int) $db->fetch("SELECT COUNT(*) c FROM users WHERE role = 'user' AND status = 'pending'")['c'],
            'todays_registrations' => (int) $db->fetch("SELECT COUNT(*) c FROM users WHERE DATE(created_at) = CURDATE()")['c'],
            'total_deposits' => (float) $db->fetch("SELECT COALESCE(SUM(amount),0) c FROM deposits WHERE status = 'approved'")['c'],
            'pending_deposits' => (int) $db->fetch("SELECT COUNT(*) c FROM deposits WHERE status = 'pending'")['c'],
            'approved_deposits' => (int) $db->fetch("SELECT COUNT(*) c FROM deposits WHERE status = 'approved'")['c'],
            'total_withdrawals' => (float) $db->fetch("SELECT COALESCE(SUM(net_amount),0) c FROM withdrawals WHERE status = 'approved'")['c'],
            'pending_withdrawals' => (int) $db->fetch("SELECT COUNT(*) c FROM withdrawals WHERE status = 'pending'")['c'],
            'mining_income' => (float) $db->fetch("SELECT COALESCE(SUM(amount_invested),0) c FROM mining_purchases")['c'],
            'referral_earnings' => (float) $db->fetch("SELECT COALESCE(SUM(amount),0) c FROM referral_earnings")['c'],
            'task_earnings' => (float) $db->fetch("SELECT COALESCE(SUM(reward_paid),0) c FROM task_submissions WHERE status = 'approved'")['c'],
            'open_tickets' => (int) $db->fetch("SELECT COUNT(*) c FROM support_tickets WHERE status != 'closed'")['c'],
        ];
        $stats['system_revenue'] = $stats['total_deposits'] - $stats['total_withdrawals'];

        $chart = $db->fetchAll(
            "SELECT DATE(created_at) d, COUNT(*) c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) GROUP BY DATE(created_at) ORDER BY d ASC"
        );

        $latestTx = $db->fetchAll(
            "SELECT transactions.*, users.username FROM transactions JOIN users ON users.id = transactions.user_id ORDER BY transactions.created_at DESC LIMIT 8"
        );
        $recentLogins = $db->fetchAll(
            "SELECT activity_logs.*, users.username FROM activity_logs JOIN users ON users.id = activity_logs.user_id WHERE activity_logs.action = 'login' ORDER BY activity_logs.created_at DESC LIMIT 8"
        );
        $recentTickets = $db->fetchAll(
            "SELECT support_tickets.*, users.username FROM support_tickets JOIN users ON users.id = support_tickets.user_id ORDER BY support_tickets.updated_at DESC LIMIT 6"
        );

        $this->view('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'stats' => $stats,
            'chartLabels' => array_map(fn($r) => date('M j', strtotime($r['d'])), $chart),
            'chartData' => array_map(fn($r) => (int) $r['c'], $chart),
            'latestTx' => $latestTx,
            'recentLogins' => $recentLogins,
            'recentTickets' => $recentTickets,
        ], 'admin');
    }
}
