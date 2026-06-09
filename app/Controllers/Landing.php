<?php

namespace App\Controllers;

class Landing extends BaseController
{
    public function index(): string
    {
        return view('landing', [
            'title'      => 'Preventive Control System | Plataforma de Mantenimiento',
            'activeMenu' => 'landing',
            'showHeader' => false,
        ]);
    }
}
