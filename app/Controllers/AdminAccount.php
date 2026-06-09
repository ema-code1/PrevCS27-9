<?php

namespace App\Controllers;

use App\Models\UserModel;

class AdminAccount extends BaseController
{
    public function index()
    {
        $userId = (int) session('user_id');
        $user   = (new UserModel())->find($userId);

        if ($user === null) {
            session()->destroy();
            return redirect()->to(base_url('reg-log'));
        }

        return view('admin_account', [
            'title'      => 'Administrar cuenta | Preventive Control System',
            'activeMenu' => 'admin-account',
            'user'       => $user,
        ]);
    }

    public function update()
    {
        $userId    = (int) session('user_id');
        $userModel = new UserModel();
        $user      = $userModel->find($userId);

        if ($user === null) {
            return redirect()->to(base_url('reg-log'));
        }

        $rules = [
            'nombre'   => 'required|min_length[2]|max_length[150]',
            'apellido' => 'required|min_length[2]|max_length[150]',
        ];

        $newPassword     = (string) $this->request->getPost('new_password');
        $confirmPassword = (string) $this->request->getPost('confirm_password');
        $currentPassword = (string) $this->request->getPost('current_password');

        $changingPassword = ($newPassword !== '' || $confirmPassword !== '' || $currentPassword !== '');

        if ($changingPassword) {
            $rules['current_password'] = 'required';
            $rules['new_password']     = 'required|min_length[8]';
            $rules['confirm_password'] = 'required|matches[new_password]';
        }

        if (! $this->validate($rules)) {
            return redirect()->to(base_url('admin-account'))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $update = [
            'nombre'             => trim((string) $this->request->getPost('nombre')),
            'apellido'           => trim((string) $this->request->getPost('apellido')),
            'two_factor_enabled' => $this->request->getPost('two_factor_enabled') === '1',
        ];

        if ($changingPassword) {
            if (! password_verify($currentPassword, $user['password_hash'])) {
                return redirect()->to(base_url('admin-account'))
                    ->withInput()
                    ->with('error', 'La contraseña actual no es correcta.');
            }
            $update['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $userModel->update($userId, $update);

        session()->set([
            'user_name' => trim($update['nombre'] . ' ' . $update['apellido']),
        ]);

        return redirect()->to(base_url('admin-account'))
            ->with('success', 'Cuenta actualizáda correctamente.');
    }
}
