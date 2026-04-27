<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        return view('principal', [
            'title'      => 'Principal | Prevention Control System',
            'activeMenu' => 'panel',
        ]);
    }

    public function registrosIot(): string
    {
        return view('Registros IoT', [
            'title'      => 'Registros IoT | Prevention Control System',
            'activeMenu' => 'registros-iot',
        ]);
    }
}
