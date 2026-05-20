<?php

namespace App\Controllers;

class Landing extends BaseController
{
    public function index(): string
    {
        return view('landing', [
            'title'      => 'Prevention Control System | Plataforma de Mantenimiento',
            'activeMenu' => 'landing',
            'showHeader' => false,
        ]);
    }
}
