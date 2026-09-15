<?php

namespace App\Controllers;

use Config\Subscription;

class Pago extends BaseController
{
    public function index(): string
    {
        $session    = session();
        $isLoggedIn = (bool) $session->get('isLoggedIn');
        $plan       = config(Subscription::class);

        return view('pago', [
            'title'      => 'Pago · PrevCS Pro',
            'activeMenu' => 'pago',
            'showHeader' => false,
            'isLoggedIn' => $isLoggedIn,
            'userName'   => $isLoggedIn ? (string) $session->get('user_name') : '',
            'userEmail'  => $isLoggedIn ? (string) $session->get('user_email') : '',
            // Precio del plan: sale del .env (Config\Subscription), igual que el cobro.
            'planPrice'  => $plan->displayPrice(),
            'planAmount' => number_format($plan->amount, 2, '.', ''),
        ]);
    }
}
