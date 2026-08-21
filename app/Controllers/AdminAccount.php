<?php

namespace App\Controllers;

use App\Models\UserModel;

class AdminAccount extends BaseController
{
    /** Peso maximo aceptado para la foto de perfil. */
    private const AVATAR_MAX_BYTES = 5 * 1024 * 1024;

    /** Solo mapas de bits: nada de svg (es ejecutable) ni de formatos raros. */
    private const AVATAR_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /** Lado del cuadrado final: la foto se recorta centrada a esta medida. */
    private const AVATAR_SIZE = 512;

    /** Motivo por el que se rechazo la foto, para mostrarlo en la vista. */
    private string $avatarError = 'No se pudo guardar la foto de perfil.';

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

        // La columna se agrega por migracion: si todavia no corrio, no rompemos el guardado.
        if (db_connect()->fieldExists('phone_verification_enabled', 'users')) {
            $update['phone_verification_enabled'] = $this->request->getPost('phone_verification_enabled') === '1';
        }

        if ($changingPassword) {
            if (! password_verify($currentPassword, $user['password_hash'])) {
                return redirect()->to(base_url('admin-account'))
                    ->withInput()
                    ->with('error', 'La contraseña actual no es correcta.');
            }
            $update['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $previousPhoto = (string) ($user['foto_perfil'] ?? '');

        // Foto de perfil: primero el borrado explicito, despues la subida.
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

        $userModel->update($userId, $update);

        session()->set([
            'user_name' => trim($update['nombre'] . ' ' . $update['apellido']),
        ]);

        // La pildora del header lee la foto desde sesion: si cambio (o se
        // borro) hay que refrescar el cache para que no quede la anterior.
        if (array_key_exists('foto_perfil', $update)) {
            session()->set('user_foto', (string) ($update['foto_perfil'] ?? ''));
        }

        return redirect()->to(base_url('admin-account'))
            ->with('success', 'Cuenta actualizáda correctamente.');
    }

    /**
     * Guarda la foto que vino en el formulario, recortada a un cuadrado.
     *
     * @return string|null|false nombre del archivo guardado, null si no vino
     *                           ninguna foto, o false si hubo un error.
     */
    private function storeAvatar()
    {
        $file = $this->request->getFile('foto_perfil');

        if ($file === null || ! $file->isValid()) {
            // "No se subio ningun archivo" no es un error: solo significa que
            // el usuario guardo el formulario sin tocar la foto.
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

        // El mime del cliente se puede falsear: confirmamos que sea un bitmap real.
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

    /**
     * Recorta la imagen a un cuadrado centrado del lado AVATAR_SIZE.
     * Se hace en el servidor para no depender de lo que resuelva el navegador.
     */
    private function squareCrop(string $path): void
    {
        try {
            \Config\Services::image()
                ->withFile($path)
                ->fit(self::AVATAR_SIZE, self::AVATAR_SIZE, 'center')
                ->save($path);
        } catch (\Throwable $e) {
            // Sin GD/Imagick la foto queda con su relacion original: el CSS ya la
            // recorta al circulo, asi que no vale la pena fallar el guardado acá.
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
