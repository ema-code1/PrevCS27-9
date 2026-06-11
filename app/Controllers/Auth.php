<?php

namespace App\Controllers;

use App\Libraries\VerificationCodeService;
use App\Libraries\VerificationMailer;
use App\Models\IndustryUserModel;
use App\Models\UserModel;

class Auth extends BaseController
{
    private const RESET_TOKEN_EXPIRATION_SECONDS = 900;
    private const RESET_LINK_SENT_MESSAGE = 'Si el correo existe, se enviara un enlace.';
    private const RESET_LINK_ERROR_MESSAGE = 'No pudimos procesar la solicitud en este momento. Intentá nuevamente más tarde.';
    private const RESET_LINK_INVALID_MESSAGE = 'El enlace de recuperación no es valido o ya expiro.';

    public function index()
    {
        if (session('isLoggedIn') === true) {
            return redirect()->to(base_url('principal'));
        }

        // Si la URI fue /register, abrir el tab de registro por defecto
        $path = trim((string) $this->request->getUri()->getPath(), '/');
        $defaultForm = str_ends_with($path, 'register') ? 'register' : 'login';

        return view('reg-log', [
            'title'           => 'Acceso | Preventive Control System',
            'activeForm'      => session()->getFlashdata('activeForm') ?? $defaultForm,
            'hideAuthButtons' => true,
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

        if (empty($user['email_verified_at'])) {
            $this->startPendingFlow($user, VerificationCodeService::PURPOSE_EMAIL_VERIFICATION);
            $sent = $this->issueAndSend($user, VerificationCodeService::PURPOSE_EMAIL_VERIFICATION);

            $redirect = redirect()->to(base_url('verificar-cuenta'));
            return $sent
                ? $redirect
                : $redirect->with('error', 'No pudimos enviar el correo. Podes intentar de nuevo.');
        }

        if ((bool) ($user['two_factor_enabled'] ?? false)) {
            $this->startPendingFlow($user, VerificationCodeService::PURPOSE_TWO_FACTOR_LOGIN);
            $sent = $this->issueAndSend($user, VerificationCodeService::PURPOSE_TWO_FACTOR_LOGIN);

            $redirect = redirect()->to(base_url('verificar-login'));
            return $sent
                ? $redirect
                : $redirect->with('error', 'No pudimos enviar el codigo. Podes intentar de nuevo.');
        }

        $this->completeLogin($user);

        return redirect()->to(base_url('principal'));
    }

    /**
     * Setea en sesión la industria activa del usuario (primera membresia),
     * para scopear las vistas de ESP32/IoT. Si no tiene ninguna, no hace nada.
     */
    private function setActiveIndustry(int $userId): void
    {
        $industries = (new IndustryUserModel())->industriesForUser($userId);

        if ($industries === []) {
            return;
        }

        session()->set([
            'active_industry_id'   => (int) $industries[0]['id'],
            'active_industry_role' => $industries[0]['role'],
        ]);
    }

    private function completeLogin(array $user): void
    {
        session()->regenerate(true);
        session()->remove([
            'verification_user_id',
            'two_factor_user_id',
        ]);
        session()->set([
            'user_id'        => $user['id'],
            'user_name'      => trim($user['nombre'] . ' ' . $user['apellido']),
            'user_email'     => $user['email'],
            'email_verified' => true,
            'isLoggedIn'     => true,
        ]);

        $this->setActiveIndustry((int) $user['id']);
    }

    public function register()
    {
        $rules = [
            'nombre'           => 'required|min_length[2]|max_length[150]',
            'apellido'         => 'required|min_length[2]|max_length[150]',
            'email'            => 'required|valid_email|is_unique[users.email]',
            'password'         => 'required|min_length[6]',
            'password_confirm' => 'required|matches[password]',
        ];

        $messages = [
            'email' => [
                'is_unique' => 'Ese email ya esta registrado.',
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

        $insertData = [
            'nombre'        => trim((string) $this->request->getPost('nombre')),
            'apellido'      => trim((string) $this->request->getPost('apellido')),
            'email'         => trim((string) $this->request->getPost('email')),
            'password_hash' => (string) $this->request->getPost('password'),
        ];

        try {
            $insertResult = $userModel->insert($insertData);
        } catch (\Throwable $e) {
            $insertResult = false;
            log_message('error', 'Register failed for {email}: {message}', [
                'email'   => (string) $this->request->getPost('email'),
                'message' => $e->getMessage(),
            ]);
        }

        if ($insertResult === false) {
            return redirect()->to(base_url('login'))
                ->withInput()
                ->with('activeForm', 'register')
                ->with('error', 'No se pudo crear la cuenta. Revisa la estructura de la tabla users.');
        }

        $user = $userModel->find($userModel->getInsertID());

        if ($user === null) {
            return redirect()->to(base_url('login'))
                ->with('activeForm', 'login')
                ->with('error', 'La cuenta fue creada, pero no pudimos iniciar la verificacion.');
        }

        session()->regenerate(true);
        $this->startPendingFlow($user, VerificationCodeService::PURPOSE_EMAIL_VERIFICATION);
        $sent = $this->issueAndSend($user, VerificationCodeService::PURPOSE_EMAIL_VERIFICATION);

        $redirect = redirect()->to(base_url('verificar-cuenta'));
        return $sent
            ? $redirect->with('success', 'Cuenta creada. Revisa tu correo.')
            : $redirect->with('error', 'Cuenta creada, pero no pudimos enviar el correo. Reintenta.');
    }

    public function verificationForm()
    {
        $user = $this->pendingUser(VerificationCodeService::PURPOSE_EMAIL_VERIFICATION);
        if ($user === null) {
            return redirect()->to(base_url('login'))
                ->with('error', 'Inicia sesion para verificar tu cuenta.');
        }

        if (! empty($user['email_verified_at'])) {
            $this->completeLogin($user);
            return redirect()->to(base_url('cuenta-creada'));
        }

        return $this->verificationView(
            $user,
            VerificationCodeService::PURPOSE_EMAIL_VERIFICATION,
            'Verificacion de correo',
            'Ingresa el codigo',
            'Verifica tu cuenta',
            base_url('verificar-cuenta'),
            base_url('verificar-cuenta/reenviar'),
        );
    }

    public function verifyAccountCode()
    {
        $user = $this->pendingUser(VerificationCodeService::PURPOSE_EMAIL_VERIFICATION);
        if ($user === null) {
            return redirect()->to(base_url('login'))
                ->with('error', 'La verificacion vencio. Inicia sesion de nuevo.');
        }

        $result = (new VerificationCodeService())->verify(
            (int) $user['id'],
            VerificationCodeService::PURPOSE_EMAIL_VERIFICATION,
            (string) $this->request->getPost('code'),
        );

        if ($result['status'] !== VerificationCodeService::STATUS_VALID) {
            return redirect()->to(base_url('verificar-cuenta'))
                ->with('error', $this->verificationError($result));
        }

        $verifiedAt = date('Y-m-d H:i:s');
        (new UserModel())->update((int) $user['id'], [
            'email_verified_at' => $verifiedAt,
        ]);
        $user['email_verified_at'] = $verifiedAt;
        $this->completeLogin($user);

        return redirect()->to(base_url('cuenta-creada'));
    }

    public function resendAccountCode()
    {
        return $this->resendPendingCode(
            VerificationCodeService::PURPOSE_EMAIL_VERIFICATION,
            'verificar-cuenta',
        );
    }

    public function twoFactorForm()
    {
        if (session('isLoggedIn') === true) {
            return redirect()->to(base_url('principal'));
        }

        $user = $this->pendingUser(VerificationCodeService::PURPOSE_TWO_FACTOR_LOGIN);
        if ($user === null || ! (bool) ($user['two_factor_enabled'] ?? false)) {
            return redirect()->to(base_url('login'))
                ->with('error', 'Inicia sesion de nuevo.');
        }

        return $this->verificationView(
            $user,
            VerificationCodeService::PURPOSE_TWO_FACTOR_LOGIN,
            'Seguridad',
            'Ingresa tu codigo 2FA',
            'Confirma el inicio de sesion',
            base_url('verificar-login'),
            base_url('verificar-login/reenviar'),
        );
    }

    public function verifyTwoFactorCode()
    {
        $user = $this->pendingUser(VerificationCodeService::PURPOSE_TWO_FACTOR_LOGIN);
        if ($user === null || ! (bool) ($user['two_factor_enabled'] ?? false)) {
            return redirect()->to(base_url('login'))
                ->with('error', 'La verificacion vencio. Inicia sesion de nuevo.');
        }

        $result = (new VerificationCodeService())->verify(
            (int) $user['id'],
            VerificationCodeService::PURPOSE_TWO_FACTOR_LOGIN,
            (string) $this->request->getPost('code'),
        );

        if ($result['status'] !== VerificationCodeService::STATUS_VALID) {
            return redirect()->to(base_url('verificar-login'))
                ->with('error', $this->verificationError($result));
        }

        $this->completeLogin($user);

        return redirect()->to(base_url('principal'));
    }

    public function resendTwoFactorCode()
    {
        return $this->resendPendingCode(
            VerificationCodeService::PURPOSE_TWO_FACTOR_LOGIN,
            'verificar-login',
        );
    }

    public function accountCreated()
    {
        return view('onboarding/cuenta-creada', [
            'title'           => 'Cuenta creada | Preventive Control System',
            'hideAuthButtons' => true,
        ]);
    }

    private function startPendingFlow(array $user, string $purpose): void
    {
        session()->remove([
            'user_id',
            'user_name',
            'user_email',
            'email_verified',
            'isLoggedIn',
            'verification_user_id',
            'two_factor_user_id',
            'active_industry_id',
            'active_industry_role',
        ]);

        $key = $purpose === VerificationCodeService::PURPOSE_TWO_FACTOR_LOGIN
            ? 'two_factor_user_id'
            : 'verification_user_id';
        session()->set($key, (int) $user['id']);
    }

    private function issueAndSend(array $user, string $purpose): bool
    {
        $codes = new VerificationCodeService();
        $issued = $codes->issue((int) $user['id'], $purpose);

        if ($issued['cooldown'] === true) {
            return true;
        }

        if ((new VerificationMailer())->send($user, $purpose, (string) $issued['code'])) {
            return true;
        }

        $codes->discard((int) $user['id'], $purpose);
        return false;
    }

    private function pendingUser(string $purpose): ?array
    {
        $key = $purpose === VerificationCodeService::PURPOSE_TWO_FACTOR_LOGIN
            ? 'two_factor_user_id'
            : 'verification_user_id';
        $userId = (int) session($key);

        if ($userId <= 0) {
            return null;
        }

        return (new UserModel())->find($userId);
    }

    private function verificationView(
        array $user,
        string $purpose,
        string $title,
        string $eyebrow,
        string $heading,
        string $verifyUrl,
        string $resendUrl,
    ) {
        return view('auth/verification_code', [
            'title'           => $title . ' | Preventive Control System',
            'hideAuthButtons' => true,
            'eyebrow'         => $eyebrow,
            'heading'         => $heading,
            'maskedEmail'     => $this->maskEmail((string) $user['email']),
            'verifyUrl'       => $verifyUrl,
            'resendUrl'       => $resendUrl,
            'retryAfter'      => (new VerificationCodeService())->retryAfter((int) $user['id'], $purpose),
        ]);
    }

    private function resendPendingCode(string $purpose, string $redirectPath)
    {
        $user = $this->pendingUser($purpose);
        if ($user === null) {
            return redirect()->to(base_url('login'))
                ->with('error', 'La verificacion vencio. Inicia sesion de nuevo.');
        }

        $codes = new VerificationCodeService();
        $issued = $codes->issue((int) $user['id'], $purpose);

        if ($issued['cooldown'] === true) {
            return redirect()->to(base_url($redirectPath))
                ->with('error', 'Espera ' . $issued['retry_after'] . ' segundos para reenviar.');
        }

        if (! (new VerificationMailer())->send($user, $purpose, (string) $issued['code'])) {
            $codes->discard((int) $user['id'], $purpose);
            return redirect()->to(base_url($redirectPath))
                ->with('error', 'No pudimos enviar el correo. Intenta de nuevo.');
        }

        return redirect()->to(base_url($redirectPath))
            ->with('success', 'Codigo enviado.');
    }

    private function verificationError(array $result): string
    {
        return match ($result['status']) {
            VerificationCodeService::STATUS_INVALID => 'Codigo incorrecto. Te quedan ' . $result['attempts_left'] . ' intentos.',
            VerificationCodeService::STATUS_EXPIRED => 'El codigo vencio. Envia uno nuevo.',
            VerificationCodeService::STATUS_USED => 'Ese codigo ya fue usado. Envia uno nuevo.',
            VerificationCodeService::STATUS_LOCKED => 'Llegaste al maximo de 5 intentos. Envia un codigo nuevo.',
            default => 'No hay un codigo activo. Envia uno nuevo.',
        };
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));
        $hidden = str_repeat('*', max(2, mb_strlen($local) - mb_strlen($visible)));

        return $visible . $hidden . ($domain !== '' ? '@' . $domain : '');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to(base_url('reg-log'));
    }

    public function forgotPassword()
    {
        return view('auth/forgot_password', [
            'title' => 'Recuperar contraseña | Preventive Control System',
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
        $emailService->setSubject('Recuperación de contraseña');
        $emailService->setMailType('html');
        $emailService->setMessage(view('emails/reset_password', ['resetLink' => $resetLink]));

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
            'title' => 'Nueva contraseña | Preventive Control System',
            'token' => $token,
        ]);
    }

    public function updatePassword()
    {
        $rules = [
            'token'            => 'required',
            'password'         => 'required|min_length[6]',
            'password_confirm' => 'required|matches[password]',
        ];

        $messages = [
            'password_confirm' => [
                'matches' => 'Las contraseñas no coinciden.',
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
                ->with('error', 'No pudimos actualizar la contraseña. Intentá nuevamente.');
        }

        $this->deletePasswordReset($passwordResetBuilder, $matchedReset);

        return redirect()->to(base_url('login'))
            ->with('success', 'Contraseña actualizáda correctamente. Ya puedes iniciar sesión.');
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
