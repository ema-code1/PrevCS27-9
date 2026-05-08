<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    private const RESET_TOKEN_EXPIRATION_SECONDS = 900;

    public function index()
    {
        if (session('isLoggedIn') === true) {
            return redirect()->to(base_url('principal'));
        }

        return view('reg-log', [
            'title'      => 'Acceso | Prevention Control System',
            'activeForm' => session()->getFlashdata('activeForm') ?? 'login',
        ]);
    }

    public function login()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to(base_url('login'))
                ->withInput()
                ->with('activeForm', 'login')
                ->with('errors', $this->validator->getErrors());
        }

        $userModel = new UserModel();
        $user = $userModel->where('email', $this->request->getPost('email'))->first();

        if ($user === null || ! password_verify((string) $this->request->getPost('password'), $user['password_hash'])) {
            return redirect()->to(base_url('login'))
                ->withInput()
                ->with('activeForm', 'login')
                ->with('error', 'Email o contraseña incorrectos.');
        }

        session()->set([
            'user_id'    => $user['id'],
            'user_name'  => trim($user['nombre'] . ' ' . $user['apellido']),
            'user_email' => $user['email'],
            'isLoggedIn' => true,
        ]);

        return redirect()->to(base_url('principal'));
    }

    public function register()
    {
        // #region agent log
        $debugLog = static function (string $hypothesisId, string $message, array $data = []): void {
            $payload = [
                'sessionId'    => 'a6e4ca',
                'runId'        => 'reg-register-pre-fix',
                'hypothesisId' => $hypothesisId,
                'location'     => 'Auth.php:register',
                'message'      => $message,
                'data'         => $data,
                'timestamp'    => (int) round(microtime(true) * 1000),
            ];
            @file_put_contents(ROOTPATH . 'debug-a6e4ca.log', json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
        };
        // #endregion

        $rules = [
            'name'              => 'required|min_length[3]|max_length[100]',
            'email'             => 'required|valid_email|is_unique[users.email]',
            'password'          => 'required|min_length[6]',
            'password_confirm'  => 'required|matches[password]',
        ];

        $messages = [
            'email' => [
                'is_unique' => 'Ese email ya está registrado.',
            ],
            'password_confirm' => [
                'matches' => 'Las contraseñas no coinciden.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->to(base_url('login'))
                ->withInput()
                ->with('activeForm', 'register')
                ->with('errors', $this->validator->getErrors());
        }

        $userModel = new UserModel();
        $columns = [];
        try {
            $columnsResult = $userModel->db->query('SHOW COLUMNS FROM users')->getResultArray();
            $columns = array_map(static fn ($col) => $col['Field'] ?? '', $columnsResult);
        } catch (\Throwable $e) {
            $columns = ['show_columns_failed'];
        }

        // #region agent log
        $debugLog('H4', 'Users table runtime columns', [
            'columns' => $columns,
        ]);
        // #endregion

        $fullName = preg_split('/\s+/', trim((string) $this->request->getPost('name')), 2);

        $insertData = [
            'nombre'        => $fullName[0] ?? '',
            'apellido'      => $fullName[1] ?? '',
            'email'         => trim((string) $this->request->getPost('email')),
            'password_hash' => (string) $this->request->getPost('password'),
        ];

        $allowedFields = [];
        try {
            $reflection = new \ReflectionClass($userModel);
            $property = $reflection->getProperty('allowedFields');
            $property->setAccessible(true);
            $allowedFields = (array) $property->getValue($userModel);
        } catch (\Throwable $e) {
            $allowedFields = ['reflection_failed'];
        }

        // #region agent log
        $debugLog('H1', 'Register payload vs model allowed fields', [
            'insertKeys' => array_keys($insertData),
            'allowedFields' => $allowedFields,
        ]);
        // #endregion

        try {
            $insertResult = $userModel->insert($insertData);
            $dbError = $userModel->db->error();
        } catch (\Throwable $e) {
            // #region agent log
            $debugLog('H2', 'Insert threw exception', [
                'exceptionClass' => get_class($e),
                'exceptionMessage' => $e->getMessage(),
            ]);
            // #endregion
            $insertResult = false;
            $dbError = ['code' => -1, 'message' => $e->getMessage()];
        }

        // #region agent log
        $debugLog('H2', 'Insert result and DB error snapshot', [
            'insertResult' => $insertResult,
            'insertId' => $userModel->getInsertID(),
            'modelErrors' => $userModel->errors(),
            'dbErrorCode' => $dbError['code'] ?? null,
            'dbErrorMessage' => $dbError['message'] ?? null,
        ]);
        // #endregion

        if ($insertResult === false) {
            return redirect()->to(base_url('login'))
                ->withInput()
                ->with('activeForm', 'register')
                ->with('error', 'No se pudo crear la cuenta. Revisa la estructura de la tabla users.');
        }

        $user = $userModel->find($userModel->getInsertID());

        // #region agent log
        $debugLog('H3', 'Fetched inserted user keys', [
            'userFound' => $user !== null,
            'userKeys' => is_array($user) ? array_keys($user) : [],
        ]);
        // #endregion

        session()->set([
            'user_id'    => $user['id'],
            'user_name'  => trim($user['nombre'] . ' ' . $user['apellido']),
            'user_email' => $user['email'],
            'isLoggedIn' => true,
        ]);

        return redirect()->to(base_url('principal'));
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to(base_url('reg-log'));
    }

    public function forgotPassword()
    {
        return view('auth/forgot_password', [
            'title' => 'Recuperar contrasena | Prevention Control System',
        ]);
    }

    public function sendResetLink()
    {
        $rules = [
            'email' => 'required|valid_email',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to(base_url('forgot-password'))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $email = trim((string) $this->request->getPost('email'));
        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        if ($user !== null) {
            $token = bin2hex(random_bytes(50));
            $hashedToken = password_hash($token, PASSWORD_DEFAULT);

            $db = \Config\Database::connect();
            $passwordResetBuilder = $db->table('password_resets');
            $passwordResetBuilder->where('email', $email)->delete();
            $passwordResetBuilder->insert([
                'email'      => $email,
                'token'      => $hashedToken,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $resetLink = base_url("reset-password/{$token}");

            $emailService = \Config\Services::email();
            $emailService->setTo($email);
            $emailService->setSubject('Recuperacion de contrasena');
            $emailService->setMessage(
                "Hola,\n\nRecibimos una solicitud para restablecer tu contrasena.\n" .
                "Haz clic en el siguiente enlace para continuar:\n{$resetLink}\n\n" .
                'Este enlace expira en 15 minutos.'
            );
            $emailService->send();
        }

        return redirect()->to(base_url('forgot-password'))
            ->with('success', 'Si el correo existe, se enviara un enlace.');
    }

    public function resetPassword(string $token)
    {
        $db = \Config\Database::connect();
        $passwordResetBuilder = $db->table('password_resets');
        $passwordResets = $passwordResetBuilder->get()->getResultArray();

        $matchedReset = null;
        foreach ($passwordResets as $passwordReset) {
            if (password_verify($token, (string) ($passwordReset['token'] ?? ''))) {
                $matchedReset = $passwordReset;
                break;
            }
        }

        if ($matchedReset === null) {
            return redirect()->to(base_url('forgot-password'))
                ->with('error', 'El enlace de recuperacion no es valido o ya expiro.');
        }

        $createdAt = strtotime((string) ($matchedReset['created_at'] ?? ''));
        if ($createdAt === false || (time() - $createdAt) > self::RESET_TOKEN_EXPIRATION_SECONDS) {
            $passwordResetBuilder->where('email', $matchedReset['email'] ?? '')->delete();

            return redirect()->to(base_url('forgot-password'))
                ->with('error', 'El enlace de recuperacion no es valido o ya expiro.');
        }

        return view('auth/reset_password', [
            'title' => 'Nueva contrasena | Prevention Control System',
            'token' => $token,
        ]);
    }

    public function updatePassword()
    {
        $rules = [
            'token'    => 'required',
            'password' => 'required|min_length[6]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $token = (string) $this->request->getPost('token');
        $newPassword = (string) $this->request->getPost('password');

        $db = \Config\Database::connect();
        $passwordResetBuilder = $db->table('password_resets');
        $passwordResets = $passwordResetBuilder->get()->getResultArray();

        $matchedReset = null;
        foreach ($passwordResets as $passwordReset) {
            if (password_verify($token, (string) ($passwordReset['token'] ?? ''))) {
                $matchedReset = $passwordReset;
                break;
            }
        }

        if ($matchedReset === null) {
            return redirect()->to(base_url('forgot-password'))
                ->with('error', 'El enlace de recuperacion no es valido o ya expiro.');
        }

        $createdAt = strtotime((string) ($matchedReset['created_at'] ?? ''));
        if ($createdAt === false || (time() - $createdAt) > self::RESET_TOKEN_EXPIRATION_SECONDS) {
            $passwordResetBuilder->where('email', $matchedReset['email'] ?? '')->delete();

            return redirect()->to(base_url('forgot-password'))
                ->with('error', 'El enlace de recuperacion no es valido o ya expiro.');
        }

        $userModel = new UserModel();
        $userModel->where('email', $matchedReset['email'] ?? '')
            ->set(['password_hash' => $newPassword])
            ->update();

        $passwordResetBuilder->where('email', $matchedReset['email'] ?? '')->delete();

        return redirect()->to(base_url('login'))
            ->with('success', 'Contrasena actualizada correctamente. Ya puedes iniciar sesion.');
    }
}
