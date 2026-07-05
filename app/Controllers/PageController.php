<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Core\Session;
use App\Models\MiningPlan;
use App\Models\ReferralEarning;

class PageController extends Controller
{
    public function home(): void
    {
        $this->view('pages/home', [
            'title' => 'Home',
            'plans' => MiningPlan::activePlans(),
            'leaders' => ReferralEarning::leaderboard(5),
            'totalUsers' => (int) db()->fetch("SELECT COUNT(*) c FROM users WHERE role = 'user'")['c'],
            'totalPaid' => (float) db()->fetch("SELECT COALESCE(SUM(net_amount),0) c FROM withdrawals WHERE status = 'approved'")['c'],
        ]);
    }

    public function about(): void
    {
        $this->view('pages/about', ['title' => 'About Us']);
    }

    public function faq(): void
    {
        $this->view('pages/faq', ['title' => 'FAQ']);
    }

    public function contact(): void
    {
        if ($this->isPost()) {
            $this->verifyCsrf();
            $email = strtolower(trim((string) $this->post('email')));

            if (RateLimiter::tooManyAttempts($email, 'contact_form', 3, 15)) {
                Session::flash('error', 'You have sent too many messages recently. Please try again later.');
                $this->redirect('contact');
            }
            RateLimiter::hit($email, 'contact_form', false);

            $name = sanitize($this->post('name'));
            $message = sanitize($this->post('message'));
            $supportEmail = setting('contact_email', 'support@9jacash.com');

            $body = "<p><strong>From:</strong> " . e($name) . " (" . e($email) . ")</p><p>" . nl2br(e($message)) . "</p>";
            Mailer::send($supportEmail, 'New Contact Form Message', Mailer::template('New Contact Message', $body));

            Session::flash('success', 'Your message has been sent. We will get back to you shortly.');
            $this->redirect('contact');
        }

        $this->view('pages/contact', ['title' => 'Contact Us']);
    }

    public function terms(): void
    {
        $this->view('pages/terms', ['title' => 'Terms of Service']);
    }

    public function privacy(): void
    {
        $this->view('pages/privacy', ['title' => 'Privacy Policy']);
    }

    public function pricing(): void
    {
        $this->view('pages/pricing', [
            'title' => 'Mining Plans & Pricing',
            'plans' => MiningPlan::activePlans(),
        ]);
    }

    public function miningInfo(): void
    {
        $this->view('pages/mining-info', ['title' => 'How Mining Works']);
    }

    public function referralInfo(): void
    {
        $this->view('pages/referral-info', ['title' => 'Referral Program']);
    }

    public function maintenance(): void
    {
        $this->view('pages/maintenance', ['title' => 'Under Maintenance'], null);
    }
}
