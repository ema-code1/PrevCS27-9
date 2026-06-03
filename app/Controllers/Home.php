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
}
