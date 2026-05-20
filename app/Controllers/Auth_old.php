<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    private const RESET_TOKEN_EXPIRATION_SECONDS = 900;
    private const RESET_LINK_SENT_MESSAGE = 'Si el correo existe, se enviara un enlace.';
    private const RESET_LINK_ERROR_MESSAGE = 'No pudimos procesar la solicitud en este momento. Intentalo nuevamente mas tarde.';
    private const RESET_LINK_INVALID_MESSAGE = 'El enlace de recuperacion no es valido o ya expiro.';

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

        if ($user === null) {
            return redirect()->to(base_url('forgot-password'))
                ->with('success', self::RESET_LINK_SENT_MESSAGE);
        }

        $token = bin2hex(random_bytes(50));
        $hashedToken = password_hash($token, PASSWORD_DEFAULT);

        $db = \Config\Database::connect();
        $passwordResetBuilder = $db->table('password_resets');

        try {
            $passwordResetBuilder->where('user_id', $user['id'])->delete();
            $passwordResetBuilder->insert([
                'user_id'    => $user['id'],
                'token'      => $hashedToken,
                'created_at' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', time() + self::RESET_TOKEN_EXPIRATION_SECONDS),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Password reset token could not be stored for {email}: {message}', [
                'email'   => $email,
                'message' => $e->getMessage(),
            ]);

            return redirect()->to(base_url('forgot-password'))
                ->withInput()
                ->with('error', self::RESET_LINK_ERROR_MESSAGE);
        }

        $resetLink = base_url("reset-password/{$token}");
        $emailConfig = config('Email');
        $fromEmail = $emailConfig->fromEmail !== '' ? $emailConfig->fromEmail : $emailConfig->SMTPUser;
        $fromName = $emailConfig->fromName !== '' ? $emailConfig->fromName : 'Sistema';

        if ($fromEmail === '') {
            $passwordResetBuilder->where('user_id', $user['id'])->delete();
            log_message('error', 'Password reset email could not be sent because fromEmail/SMTPUser is empty.');

            return redirect()->to(base_url('forgot-password'))
                ->withInput()
                ->with('error', self::RESET_LINK_ERROR_MESSAGE);
        }

        $emailService = \Config\Services::email();
        $emailService->setFrom($fromEmail, $fromName);
        $emailService->setTo($email);
        $emailService->setSubject('Recuperacion de contrasena');
        $emailService->setMessage(
            "Hola,<br><br>" .
            "Recibimos una solicitud para restablecer tu contrasena.<br>" .
            "Hace clic en el siguiente enlace para continuar:<br>" .
            '<a href="' . esc($resetLink, 'attr') . '">' . esc($resetLink) . '</a><br><br>' .
            'Este enlace expira en 15 minutos.'
        );

        if (! $emailService->send()) {
            $debug = $emailService->printDebugger(['headers', 'subject']);
            $passwordResetBuilder->where('user_id', $user['id'])->delete();
            log_message('error', 'Password reset email failed for {email}: {debug}', [
                'email' => $email,
                'debug' => $debug,
            ]);

            return redirect()->to(base_url('forgot-password'))
                ->withInput()
                ->with('error', self::RESET_LINK_ERROR_MESSAGE);
        }

        return redirect()->to(base_url('forgot-password'))
            ->with('success', self::RESET_LINK_SENT_MESSAGE);
    }

    public function resetPassword(string $token)
    {
        $db = \Config\Database::connect();
        $passwordResetBuilder = $db->table('password_resets');
        $matchedReset = $this->findPasswordResetByToken($passwordResetBuilder, $token);

        if ($matchedReset === null) {
            return redirect()->to(base_url('forgot-password'))
                ->with('error', self::RESET_LINK_INVALID_MESSAGE);
        }

        if ($this->passwordResetExpired($matchedReset)) {
            $this->deletePasswordReset($passwordResetBuilder, $matchedReset);

            return redirect()->to(base_url('forgot-password'))
                ->with('error', self::RESET_LINK_INVALID_MESSAGE);
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
            'password_confirm' => 'required|matches[password]',
        ];

        $messages = [
            'password_confirm' => [
                'matches' => 'Las contrasenas no coinciden.',
            ],
        ];

        $token = (string) $this->request->getPost('token');

        if (! $this->validate($rules, $messages)) {
            $redirectUrl = $token !== '' ? base_url("reset-password/{$token}") : base_url('forgot-password');

            return redirect()->to($redirectUrl)
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $newPassword = (string) $this->request->getPost('password');

        $db = \Config\Database::connect();
        $passwordResetBuilder = $db->table('password_resets');
        $matchedReset = $this->findPasswordResetByToken($passwordResetBuilder, $token);

        if ($matchedReset === null) {
            return redirect()->to(base_url('forgot-password'))
                ->with('error', self::RESET_LINK_INVALID_MESSAGE);
        }

        if ($this->passwordResetExpired($matchedReset)) {
            $this->deletePasswordReset($passwordResetBuilder, $matchedReset);

            return redirect()->to(base_url('forgot-password'))
                ->with('error', self::RESET_LINK_INVALID_MESSAGE);
        }

        $userModel = new UserModel();
        $user = $userModel->find($matchedReset['user_id'] ?? null);
        $updated = false;

        if ($user !== null) {
            $updated = $db->table('users')
                ->where('id', $user['id'])
                ->update(['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)]);
        }

        if ($updated === false) {
            log_message('error', 'Password reset update failed for user_id {userId}: {errors}', [
                'userId' => (string) ($matchedReset['user_id'] ?? ''),
                'errors' => json_encode($userModel->errors(), JSON_UNESCAPED_UNICODE),
            ]);

            return redirect()->to(base_url("reset-password/{$token}"))
                ->with('error', 'No pudimos actualizar la contrasena. Intentalo nuevamente.');
        }

        $this->deletePasswordReset($passwordResetBuilder, $matchedReset);

        return redirect()->to(base_url('login'))
            ->with('success', 'Contrasena actualizada correctamente. Ya puedes iniciar sesion.');
    }

    private function findPasswordResetByToken($passwordResetBuilder, string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        $passwordResets = $passwordResetBuilder->get()->getResultArray();

        foreach ($passwordResets as $passwordReset) {
            if (password_verify($token, (string) ($passwordReset['token'] ?? ''))) {
                return $passwordReset;
            }
        }

        return null;
    }

    private function passwordResetExpired(array $passwordReset): bool
    {
        $expiresAt = strtotime((string) ($passwordReset['expires_at'] ?? ''));

        if ($expiresAt !== false) {
            return time() > $expiresAt;
        }

        $createdAt = strtotime((string) ($passwordReset['created_at'] ?? ''));

        return $createdAt === false || (time() - $createdAt) > self::RESET_TOKEN_EXPIRATION_SECONDS;
    }

    private function deletePasswordReset($passwordResetBuilder, array $passwordReset): void
    {
        if (isset($passwordReset['id'])) {
            $passwordResetBuilder->where('id', $passwordReset['id'])->delete();

            return;
        }

        if (isset($passwordReset['user_id'])) {
            $passwordResetBuilder->where('user_id', $passwordReset['user_id'])->delete();
        }
    }
}
