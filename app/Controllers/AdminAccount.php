<?php

namespace App\Controllers;

use App\Libraries\MercadoPagoSubscriptions;
use App\Models\SubscriptionModel;
use App\Models\UserModel;
use Config\Subscription;

class AdminAccount extends BaseController
{
    /** Peso máximo aceptado para la foto de perfil. */
    private const AVATAR_MAX_BYTES = 5 * 1024 * 1024;

    /** Solo mapas de bits: nada de svg (es ejecutable) ni de formatos raros. */
    private const AVATAR_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /** Lado del cuadrado final: la foto se recorta centrada a esta medida. */
    private const AVATAR_SIZE = 512;

    /** Motivo por el que se rechazó la foto, para mostrarlo en la vista. */
    private string $avatarError = 'No se pudo guardar la foto de perfil.';

    // --------------------------------------------------------------------

    public function index()
    {
        if (session('isLoggedIn') !== true) {
            return redirect()->to(base_url('reg-log'));
        }

        $userId = (int) session('user_id');
        $user   = (new UserModel())->find($userId);

        if ($user === null) {
            session()->destroy();
            return redirect()->to(base_url('reg-log'));
        }

        return view('admin_account', [
            'title'        => 'Administrar cuenta | Preventive Control System',
            'activeMenu'   => 'admin-account',
            'user'         => $user,
            'subscription' => (new SubscriptionModel())->activeForUser($userId),
            'errors'       => session()->getFlashdata('errors') ?? [],
        ]);
    }

    // --------------------------------------------------------------------

    /**
     * Cancela la suscripción a PrevCS Pro desde la cuenta. Es lo que respalda
     * el "cancelás cuando quieras" del plan: no hace falta entrar a Mercado Pago.
     */
    public function cancelSubscription()
    {
        $userId = (int) session('user_id');
        $model  = new SubscriptionModel();
        $record = $model->activeForUser($userId);

        if ($record === null || empty($record['mp_preapproval_id'])) {
            return redirect()->to(base_url('admin-account'))
                ->with('error', 'No encontramos una suscripción activa en tu cuenta.');
        }

        try {
            $result = (new MercadoPagoSubscriptions(config(Subscription::class)))
                ->changeStatus($record['mp_preapproval_id'], 'cancelled');
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo cancelar la suscripción {ref}: {msg}', [
                'ref' => $record['external_reference'],
                'msg' => $e->getMessage(),
            ]);

            return redirect()->to(base_url('admin-account'))
                ->with('error', 'Mercado Pago no respondió. Intentá cancelar de nuevo en unos minutos.');
        }

        $model->update($record['id'], ['status' => mb_substr((string) $result['status'], 0, 20)]);

        return redirect()->to(base_url('admin-account'))
            ->with('success', 'Suscripción cancelada. No se te va a volver a cobrar.');
    }

    // --------------------------------------------------------------------

    public function update()
    {
        // Verificar sesión
        if (session('isLoggedIn') !== true) {
            return redirect()->to(base_url('reg-log'));
        }

        $userId    = (int) session('user_id');
        $userModel = new UserModel();
        $user      = $userModel->find($userId);

        if ($user === null) {
            session()->destroy();
            return redirect()->to(base_url('reg-log'));
        }

        // --------------------------------------------------------------------
        // 1. Reglas de validación
        // --------------------------------------------------------------------
        $postedNombre     = trim((string) $this->request->getPost('nombre'));
        $postedApellido   = trim((string) $this->request->getPost('apellido'));
        $postedTwoFactor  = $this->request->getPost('two_factor_enabled') === '1';
        $currentTwoFactor = ! empty($user['two_factor_enabled']);
        $changingProfile = $postedNombre !== trim((string) ($user['nombre'] ?? ''))
            || $postedApellido !== trim((string) ($user['apellido'] ?? ''))
            || $postedTwoFactor !== $currentTwoFactor;

        $rules = [
            'nombre'   => 'required|min_length[2]|max_length[150]',
            'apellido' => 'required|min_length[2]|max_length[150]',
        ];

        $newPassword     = (string) $this->request->getPost('new_password');
        $confirmPassword = (string) $this->request->getPost('confirm_password');

        $changingPassword = ($newPassword !== '' || $confirmPassword !== '');
        $requiresPassword = $changingPassword || $changingProfile;

        if ($changingPassword) {
            $rules['new_password']     = 'required|min_length[8]';
            $rules['confirm_password'] = 'required|matches[new_password]';
        }

        if ($requiresPassword) {
            $rules['current_password'] = 'required';
        }

        $messages = [
            'confirm_password' => [
                'matches' => 'Las contraseñas no coinciden.',
            ],
            'current_password' => [
                'required' => 'Debes ingresar tu contraseña actual para guardar los cambios.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->to(base_url('admin-account'))
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('error', 'Revisá los datos del formulario.');
        }

        // --------------------------------------------------------------------
        // 2. Verificar contraseña actual cuando corresponde
        // --------------------------------------------------------------------
        $currentPassword = (string) $this->request->getPost('current_password');
        if (($requiresPassword || $currentPassword !== '') && ! password_verify($currentPassword, $user['password_hash'])) {
            return redirect()->to(base_url('admin-account'))
                ->withInput()
                ->with('errors', ['current_password' => 'La contraseña actual es incorrecta.'])
                ->with('error', 'La contraseña actual es incorrecta.');
        }

        // --------------------------------------------------------------------
        // 3. Preparar datos para actualizar (nombre, apellido, opciones de seguridad)
        // --------------------------------------------------------------------
        $update = [
            'nombre'   => $postedNombre,
            'apellido' => $postedApellido,
        ];

        // Seguridad (2FA, verificación telefónica)
        $update['two_factor_enabled'] = $postedTwoFactor;

        /*
        if (db_connect()->fieldExists('phone_verification_enabled', 'users')) {
            $update['phone_verification_enabled'] = $this->request->getPost('phone_verification_enabled') === '1';
        }
        */

        // Cambio de contraseña (si se enviaron nuevos campos)
        if ($changingPassword) {
            $update['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        // --------------------------------------------------------------------
        // 4. Manejo de la foto de perfil
        // --------------------------------------------------------------------
        $previousPhoto = (string) ($user['foto_perfil'] ?? '');

        // Borrado explícito (aunque la vista no lo usa, se mantiene por si acaso)
        if ($this->request->getPost('remove_foto_perfil') === '1') {
            $update['foto_perfil'] = null;
            $this->deleteAvatarFile($previousPhoto);
            $previousPhoto = '';
        }

        $photo = $this->storeAvatar();

        if ($photo === false) {
            return redirect()->to(base_url('admin-account'))
                ->withInput()
                ->with('error', $this->avatarError);
        }

        if (is_string($photo)) {
            $update['foto_perfil'] = $photo;
            $this->deleteAvatarFile($previousPhoto);
        }

        // --------------------------------------------------------------------
        // 5. Guardar en base de datos (con try-catch)
        // --------------------------------------------------------------------
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

        // --------------------------------------------------------------------
        // 6. Actualizar sesión
        // --------------------------------------------------------------------
        session()->set([
            'user_name' => trim($update['nombre'] . ' ' . $update['apellido']),
            'user_email' => $user['email'], // no cambia, pero lo refrescamos
        ]);

        if (array_key_exists('foto_perfil', $update)) {
            session()->set('user_foto', (string) ($update['foto_perfil'] ?? ''));
        }

        // --------------------------------------------------------------------
        // 7. Redirección con éxito
        // --------------------------------------------------------------------
        return redirect()->to(base_url('admin-account'))
            ->with('success', 'Cuenta actualizada correctamente.');
    }

    // --------------------------------------------------------------------
    // MÉTODOS PRIVADOS (subida, recorte y borrado de avatar)
    // --------------------------------------------------------------------

    private function storeAvatar()
    {
        $file = $this->request->getFile('foto_perfil');

        if ($file === null || ! $file->isValid()) {
            if ($file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                $this->avatarError = $file->getError() === UPLOAD_ERR_INI_SIZE || $file->getError() === UPLOAD_ERR_FORM_SIZE
                    ? 'La imagen supera los 5 MB.'
                    : 'No se pudo leer la imagen que elegiste.';
                return false;
            }
            return null;
        }

        if ($file->getSize() > self::AVATAR_MAX_BYTES) {
            $this->avatarError = 'La imagen supera los 5 MB.';
            return false;
        }

        $extension = strtolower($file->getExtension() ?: '');
        if (! in_array($extension, self::AVATAR_EXTENSIONS, true)) {
            $this->avatarError = 'Formato no permitido: usá JPG, PNG o WEBP.';
            return false;
        }

        $dimensions = @getimagesize($file->getTempName());
        if ($dimensions === false || (int) $dimensions[0] < 1 || (int) $dimensions[1] < 1) {
            $this->avatarError = 'Ese archivo no es una imagen válida.';
            return false;
        }

        $directory = FCPATH . 'uploads/avatars';
        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            $this->avatarError = 'No se pudo preparar la carpeta de fotos.';
            return false;
        }

        $storedName = $file->getRandomName();

        try {
            $file->move($directory, $storedName);
        } catch (\Throwable $e) {
            $this->avatarError = 'No se pudo guardar la imagen.';
            return false;
        }

        $this->squareCrop($directory . DIRECTORY_SEPARATOR . $storedName);

        return $storedName;
    }

    private function squareCrop(string $path): void
    {
        try {
            \Config\Services::image()
                ->withFile($path)
                ->fit(self::AVATAR_SIZE, self::AVATAR_SIZE, 'center')
                ->save($path);
        } catch (\Throwable $e) {
            log_message('warning', 'No se pudo recortar la foto de perfil: ' . $e->getMessage());
        }
    }

    private function deleteAvatarFile(string $fileName): void
    {
        $fileName = basename(trim($fileName));

        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            return;
        }

        $path = FCPATH . 'uploads/avatars' . DIRECTORY_SEPARATOR . $fileName;

        if (is_file($path)) {
            @unlink($path);
        }
    }
}
