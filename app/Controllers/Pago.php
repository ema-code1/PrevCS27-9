<?php

namespace App\Controllers;

class Pago extends BaseController
{
    public function index(): string
    {
        $session    = session();
        $isLoggedIn = (bool) $session->get('isLoggedIn');

        return view('pago', [
            'title'      => 'Pago · PrevCS Pro',
            'activeMenu' => 'pago',
            'showHeader' => false,
            'isLoggedIn' => $isLoggedIn,
            'userName'   => $isLoggedIn ? (string) $session->get('user_name') : '',
            'userEmail'  => $isLoggedIn ? (string) $session->get('user_email') : '',
        ]);
    }
}
