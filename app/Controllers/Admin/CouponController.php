<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Coupon;

class CouponController extends Controller
{
    public function index(): void
    {
        if ($this->isPost()) {
            $this->verifyCsrf();
            Coupon::create([
                'code' => strtoupper(sanitize($this->post('code'))),
                'amount' => (float) $this->post('amount'),
                'max_uses' => (int) $this->post('max_uses', 1),
                'expires_at' => $this->post('expires_at') ?: null,
                'status' => 'active',
            ]);
            Session::flash('success', 'Coupon created.');
            $this->redirect('admin/coupons');
        }

        $this->view('admin/coupons', [
            'title' => 'Coupons',
            'coupons' => Coupon::all('id DESC'),
        ], 'admin');
    }

    public function delete(int $id): void
    {
        $this->verifyCsrf();
        Coupon::deleteById($id);
        Session::flash('success', 'Coupon deleted.');
        $this->redirect('admin/coupons');
    }
}
