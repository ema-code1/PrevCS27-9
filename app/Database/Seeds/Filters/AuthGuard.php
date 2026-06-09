<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
// Clase de Autenticacion. If user logged in, allow access to  requested resource. If not, redirect to login page.
class AuthGuard implements FilterInterface
{
    // EL METODO BEFORE SE EJECUTA ANTES DE QUE SE PROCESSE LA SOLICITUD, EN ESTE CASO VERIFICA SI EL USUARIO ESTÁ LOGUEADO
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session('isLoggedIn') === true) {
            return null;
        }

        return redirect()->to(base_url('reg-log'));
    }
    // EL METODO AFTER SE EJECUTA DESPUÉS DE QUE SE PROCESSE LA SOLICITUD, EN ESTE CASO NO HACE NADA
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
