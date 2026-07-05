<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */

// ---------------------------------------------------------------------
// Public pages
// ---------------------------------------------------------------------
$router->get('/', 'PageController@home');
$router->get('about', 'PageController@about');
$router->get('faq', 'PageController@faq');
$router->any('contact', 'PageController@contact');
$router->get('terms', 'PageController@terms');
$router->get('privacy', 'PageController@privacy');
$router->get('pricing', 'PageController@pricing');
$router->get('mining-info', 'PageController@miningInfo');
$router->get('referral-info', 'PageController@referralInfo');
$router->get('maintenance', 'PageController@maintenance');

// ---------------------------------------------------------------------
// Auth
// ---------------------------------------------------------------------
$router->any('register', 'Auth\\RegisterController@handle');
$router->any('login', 'Auth\\LoginController@handle');
$router->any('login/2fa', 'Auth\\LoginController@twoFactor');
$router->post('logout', 'Auth\\LoginController@logout');
$router->any('verify-email', 'Auth\\VerifyController@handle');
$router->post('verify-email/resend', 'Auth\\VerifyController@resend');
$router->any('forgot-password', 'Auth\\PasswordController@forgot');
$router->any('reset-password', 'Auth\\PasswordController@reset');

// ---------------------------------------------------------------------
// Authenticated user area
// ---------------------------------------------------------------------
$router->group([fn() => require_login()], function ($router) {
    $router->get('dashboard', 'User\\DashboardController@index');

    $router->any('profile', 'User\\ProfileController@handle');
    $router->post('profile/avatar', 'User\\ProfileController@uploadAvatar');
    $router->any('profile/password', 'User\\ProfileController@changePassword');
    $router->any('profile/2fa', 'User\\ProfileController@twoFactor');
    $router->post('profile/2fa/disable', 'User\\ProfileController@disableTwoFactor');

    $router->get('wallet', 'User\\WalletController@index');
    $router->get('transactions', 'User\\WalletController@transactions');
    $router->post('wallet/transfer', 'User\\WalletController@transfer');

    $router->any('deposit', 'User\\DepositController@index');
    $router->post('deposit/manual', 'User\\DepositController@manualStore');
    $router->post('deposit/payvessel/init', 'User\\DepositController@payvesselInit');
    $router->get('deposit/payvessel/callback', 'User\\DepositController@payvesselCallback');
    $router->get('deposit/history', 'User\\DepositController@history');

    $router->any('withdraw', 'User\\WithdrawController@index');
    $router->post('withdraw/store', 'User\\WithdrawController@store');
    $router->post('withdraw/bank-account', 'User\\WithdrawController@addBankAccount');
    $router->post('withdraw/bank-account/{id}/default', 'User\\WithdrawController@setDefaultBankAccount');
    $router->get('withdraw/history', 'User\\WithdrawController@history');

    $router->get('mining', 'User\\MiningController@index');
    $router->post('mining/buy/{id}', 'User\\MiningController@buy');
    $router->post('mining/renew/{id}', 'User\\MiningController@renew');
    $router->get('mining/history', 'User\\MiningController@history');

    $router->get('tasks', 'User\\TaskController@index');
    $router->post('tasks/{id}/submit', 'User\\TaskController@submit');
    $router->get('tasks/history', 'User\\TaskController@history');

    $router->get('referrals', 'User\\ReferralController@index');
    $router->get('leaderboard', 'User\\ReferralController@leaderboard');

    $router->get('rewards', 'User\\RewardsController@index');
    $router->post('rewards/checkin', 'User\\RewardsController@checkin');
    $router->post('rewards/spin', 'User\\RewardsController@spin');

    $router->get('notifications', 'User\\NotificationController@index');
    $router->post('notifications/read-all', 'User\\NotificationController@markAllRead');

    $router->get('support', 'User\\SupportController@index');
    $router->any('support/new', 'User\\SupportController@create');
    $router->get('support/{id}', 'User\\SupportController@show');
    $router->post('support/{id}/reply', 'User\\SupportController@reply');
    $router->post('support/{id}/close', 'User\\SupportController@close');

    $router->any('kyc', 'User\\KycController@index');

    $router->get('files/{type}/{filename}', 'FileController@serve');
    $router->post('return-to-admin', 'ImpersonationController@returnToAdmin');
});

// ---------------------------------------------------------------------
// Admin area
// ---------------------------------------------------------------------
$router->group([fn() => require_admin()], function ($router) {
    $router->get('admin', 'Admin\\DashboardController@index');

    $router->get('admin/users', 'Admin\\UserController@index');
    $router->get('admin/users/export', 'Admin\\UserController@export');
    $router->get('admin/users/{id}', 'Admin\\UserController@show');
    $router->post('admin/users/{id}/update', 'Admin\\UserController@update');
    $router->post('admin/users/{id}/suspend', 'Admin\\UserController@suspend');
    $router->post('admin/users/{id}/activate', 'Admin\\UserController@activate');
    $router->post('admin/users/{id}/delete', 'Admin\\UserController@delete');
    $router->post('admin/users/{id}/reset-password', 'Admin\\UserController@resetPassword');
    $router->get('admin/users/{id}/login-as', 'Admin\\UserController@loginAs');

    $router->get('admin/deposits', 'Admin\\DepositController@index');
    $router->get('admin/deposits/export', 'Admin\\DepositController@export');
    $router->post('admin/deposits/{id}/approve', 'Admin\\DepositController@approve');
    $router->post('admin/deposits/{id}/reject', 'Admin\\DepositController@reject');
    $router->post('admin/deposits/{id}/edit', 'Admin\\DepositController@edit');
    $router->any('admin/payment-methods', 'Admin\\DepositController@paymentMethods');
    $router->post('admin/payment-methods/{id}/delete', 'Admin\\DepositController@deletePaymentMethod');

    $router->get('admin/withdrawals', 'Admin\\WithdrawalController@index');
    $router->get('admin/withdrawals/export', 'Admin\\WithdrawalController@export');
    $router->post('admin/withdrawals/{id}/approve', 'Admin\\WithdrawalController@approve');
    $router->post('admin/withdrawals/{id}/reject', 'Admin\\WithdrawalController@reject');

    $router->any('admin/mining/plans', 'Admin\\MiningController@plans');
    $router->post('admin/mining/plans/{id}/update', 'Admin\\MiningController@updatePlan');
    $router->post('admin/mining/plans/{id}/delete', 'Admin\\MiningController@deletePlan');
    $router->post('admin/mining/plans/{id}/toggle', 'Admin\\MiningController@togglePlan');
    $router->get('admin/mining/purchases', 'Admin\\MiningController@purchases');
    $router->post('admin/mining/purchases/{id}/force-complete', 'Admin\\MiningController@forceComplete');

    $router->any('admin/tasks', 'Admin\\TaskController@index');
    $router->post('admin/tasks/{id}/update', 'Admin\\TaskController@update');
    $router->post('admin/tasks/{id}/delete', 'Admin\\TaskController@delete');
    $router->get('admin/tasks-submissions', 'Admin\\TaskController@submissions');
    $router->post('admin/tasks-submissions/{id}/approve', 'Admin\\TaskController@approveSubmission');
    $router->post('admin/tasks-submissions/{id}/reject', 'Admin\\TaskController@rejectSubmission');

    $router->any('admin/referral-settings', 'Admin\\ReferralSettingsController@index');

    $router->get('admin/support', 'Admin\\SupportController@index');
    $router->get('admin/support/{id}', 'Admin\\SupportController@show');
    $router->post('admin/support/{id}/reply', 'Admin\\SupportController@reply');
    $router->post('admin/support/{id}/close', 'Admin\\SupportController@close');

    $router->get('admin/kyc', 'Admin\\KycController@index');
    $router->post('admin/kyc/{id}/approve', 'Admin\\KycController@approve');
    $router->post('admin/kyc/{id}/reject', 'Admin\\KycController@reject');

    $router->any('admin/settings', 'Admin\\SettingsController@index');
    $router->any('admin/announcements', 'Admin\\SettingsController@announcements');
    $router->any('admin/coupons', 'Admin\\CouponController@index');
    $router->post('admin/coupons/{id}/delete', 'Admin\\CouponController@delete');

    $router->get('admin/activity-logs', 'Admin\\LogController@index');
    $router->get('admin/webhook-logs', 'Admin\\LogController@webhooks');
});

// ---------------------------------------------------------------------
// API / Webhooks (no session middleware — verified by signature instead)
// ---------------------------------------------------------------------
$router->post('api/webhook/payvessel', 'Api\\PayVesselController@webhook');

// ---------------------------------------------------------------------
// Cron trigger for hosts without shell/CLI cron access (guarded by secret)
// ---------------------------------------------------------------------
$router->get('cron/{job}', 'Api\\CronController@run');
