<?php

namespace App\Libraries;

class VerificationMailer
{
    public function send(array $user, string $purpose, string $code): bool
    {
        $emailConfig = config('Email');
        $fromEmail = $emailConfig->fromEmail !== '' ? $emailConfig->fromEmail : $emailConfig->SMTPUser;
        $fromName = $emailConfig->fromName !== '' ? $emailConfig->fromName : 'Preventive Control System';

        if ($fromEmail === '') {
            log_message('error', 'Verification email not sent: fromEmail/SMTPUser is empty.');
            return false;
        }

        $isTwoFactor = $purpose === VerificationCodeService::PURPOSE_TWO_FACTOR_LOGIN;
        $email = service('email');
        $email->setFrom($fromEmail, $fromName);
        $email->setTo((string) $user['email']);
        $email->setSubject($isTwoFactor ? 'Tu codigo de acceso' : 'Verifica tu cuenta');
        $email->setMailType('html');
        $email->setMessage(view('emails/verification_code', [
            'code'        => $code,
            'isTwoFactor' => $isTwoFactor,
            'userName'    => trim((string) ($user['nombre'] ?? '')),
        ]));

        if ($email->send()) {
            return true;
        }

        log_message('error', 'Verification email failed for user_id {id}: {debug}', [
            'id'    => (string) ($user['id'] ?? ''),
            'debug' => $email->printDebugger(['headers', 'subject']),
        ]);

        return false;
    }
}
