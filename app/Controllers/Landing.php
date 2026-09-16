<?php

namespace App\Controllers;

use Config\Subscription;

class Landing extends BaseController
{
    public function index(): string
    {
        $plan = config(Subscription::class);

        return view('landing', [
            'title'      => 'Preventive Control System | Plataforma de Mantenimiento',
            'activeMenu' => 'landing',
            'showHeader' => false,
            // Precio del plan: sale del .env (Config\Subscription), igual que el cobro.
            'planCurrency'    => $plan->currency,
            'planAmount'      => $plan->amount,
            'planAmountLabel' => $plan->displayAmount(),
        ]);
    }
}
