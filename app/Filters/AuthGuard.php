<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
// Clase de Autenticacion. If user logged in, allow access to  requested resource. If not, redirect to login page.
class AuthGuard implements FilterInterface
{
    // EL METODO BEFORE SE EJECUTA ANTES DE QUE SE PROCESSE LA SOLICITUD, EN ESTE CASO VERIFICA SI EL USUARIO ESTÁ LOGUEADO
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session('isLoggedIn') !== true) {
            return redirect()->to(base_url('reg-log'));
        }

        if (session('email_verified') === true) {
            return null;
        }

        $user = (new UserModel())->find((int) session('user_id'));
        if ($user !== null && ! empty($user['email_verified_at'])) {
            session()->set('email_verified', true);
            return null;
        }

        session()->remove([
            'user_id',
            'user_name',
            'user_email',
            'email_verified',
            'isLoggedIn',
            'active_industry_id',
            'active_industry_role',
        ]);

        if ($user !== null) {
            session()->set('verification_user_id', (int) $user['id']);
            return redirect()->to(base_url('verificar-cuenta'));
        }

        return redirect()->to(base_url('reg-log'));
    }
    // EL METODO AFTER SE EJECUTA DESPUÉS DE QUE SE PROCESSE LA SOLICITUD, EN ESTE CASO NO HACE NADA
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
