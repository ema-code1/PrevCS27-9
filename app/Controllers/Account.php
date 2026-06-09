<?php

namespace App\Controllers;

use App\Models\UserModel;

class Account extends BaseController
{
    public function index()
    {
        if (session('isLoggedIn') !== true) {
            return redirect()->to(base_url('reg-log'));
        }

        $userModel = new UserModel();
        $user      = $userModel->find(session('user_id'));

        if ($user === null) {
            session()->destroy();

            return redirect()->to(base_url('reg-log'));
        }

        $token = session('account_csrf_token');
        if (! is_string($token) || strlen($token) !== 32) {
            $token = bin2hex(random_bytes(16));
            session()->set('account_csrf_token', $token);
        }

        return view('admin_account', [
            'title'         => 'Mi cuenta | Preventive Control System',
            'activeMenu'    => 'cuenta',
            'user'          => $user,
            'errors'        => session()->getFlashdata('errors') ?? [],
            'account_token' => $token,
        ]);
    }

    public function update()
    {
        if (session('isLoggedIn') !== true) {
            return redirect()->to(base_url('reg-log'));
        }

        $userId = (int) session('user_id');
        if ($userId <= 0) {
            session()->destroy();

            return redirect()->to(base_url('reg-log'));
        }

        // Manual CSRF check: el filtro CSRF global esta deshabilitado en Config\Filters,
        // así que validamos un token por sesión contra el campo oculto del formulario.
        $sessionToken = session('account_csrf_token');
        $postedToken  = (string) $this->request->getPost('account_token');
        if (! is_string($sessionToken) || $sessionToken === '' || ! hash_equals($sessionToken, $postedToken)) {
            return redirect()->to(base_url('admin-account'))
                ->with('error', 'Sesión vencida. Recarga la pagina e intentalo nuevamente.');
        }

        $userModel = new UserModel();
        $user      = $userModel->find($userId);
        if ($user === null) {
            session()->destroy();

            return redirect()->to(base_url('reg-log'));
        }

        $rules = [
            'nombre'   => 'required|min_length[2]|max_length[100]',
            'apellido' => 'permit_empty|max_length[100]',
            'email'    => "required|valid_email|max_length[200]|is_unique[users.email,id,{$userId}]",
        ];

        $messages = [
            'email' => [
                'is_unique' => 'Ese email ya esta en uso.',
            ],
            'new_password_confirm' => [
                'matches' => 'Las contraseñas no coinciden.',
            ],
        ];

        $changingPassword = ((string) $this->request->getPost('new_password')) !== '';
        if ($changingPassword) {
            $rules['current_password']     = 'required';
            $rules['new_password']         = 'required|min_length[6]|max_length[200]';
            $rules['new_password_confirm'] = 'required|matches[new_password]';
        }

        if (! $this->validate($rules, $messages)) {
            return redirect()->to(base_url('admin-account'))
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('error', 'Revisa los datos del formulario.');
        }

        if ($changingPassword) {
            $current = (string) $this->request->getPost('current_password');
            if (! password_verify($current, (string) $user['password_hash'])) {
                return redirect()->to(base_url('admin-account'))
                    ->withInput()
                    ->with('errors', ['current_password' => 'La contraseña actual es incorrecta.'])
                    ->with('error', 'La contraseña actual es incorrecta.');
            }
        }

        $update = [
            'nombre'   => trim((string) $this->request->getPost('nombre')),
            'apellido' => trim((string) $this->request->getPost('apellido')),
            'email'    => trim((string) $this->request->getPost('email')),
        ];

        if ($changingPassword) {
            // El hook beforeUpdate del UserModel hashea automaticamente.
            $update['password_hash'] = (string) $this->request->getPost('new_password');
        }

        try {
            $userModel->update($userId, $update);
        } catch (\Throwable $e) {
            log_message('error', 'Account update failed for user_id {id}: {msg}', [
                'id'  => $userId,
                'msg' => $e->getMessage(),
            ]);

            return redirect()->to(base_url('admin-account'))
                ->withInput()
                ->with('error', 'No pudimos guardar los cambios. Intentá nuevamente.');
        }

        session()->set([
            'user_name'  => trim($update['nombre'] . ' ' . $update['apellido']),
            'user_email' => $update['email'],
        ]);

        // Rotar token tras una operación exitosa.
        session()->set('account_csrf_token', bin2hex(random_bytes(16)));

        return redirect()->to(base_url('admin-account'))
            ->with('success', 'Cambios guardados correctamente.');
    }
}
