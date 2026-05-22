<?php

namespace App\Controllers;

class Portfolio extends BaseController
{
    public function index(): string
    {
        return view('portfolio', [
            'title'      => 'Portfolio · Emprendimientos · PrevCS',
            'activeMenu' => 'portfolio',
            'showHeader' => false,
        ]);
    }
}
