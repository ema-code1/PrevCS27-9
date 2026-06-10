<?php

namespace App\Controllers;

class Pago extends BaseController
{
    public function index(): string
    {
        return view('pago', [
            'title'      => 'Pago · PrevCS Pro',
            'activeMenu' => 'pago',
            'showHeader' => false,
        ]);
    }
}
