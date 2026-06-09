<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $isTwoFactor ? 'Codigo de acceso' : 'Verifica tu cuenta' ?></title>
</head>
<body style="margin:0;background:#f4f2ec;color:#2a2c32;font-family:Arial,sans-serif;padding:32px 16px;">
    <div style="max-width:520px;margin:0 auto;background:#ffffff;border:1px solid #deddd7;border-radius:16px;padding:32px;text-align:center;">
        <p style="margin:0 0 8px;color:#5a7339;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Preventive Control System</p>
        <h1 style="margin:0 0 12px;font-size:26px;">
            <?= $isTwoFactor ? 'Confirma tu inicio de sesion' : 'Verifica tu correo' ?>
        </h1>
        <p style="margin:0 0 24px;color:#6f7278;line-height:1.6;">
            Hola <?= esc($userName !== '' ? $userName : 'usuario') ?>. Usa este codigo. Vence en 5 minutos.
        </p>
        <div style="font-family:monospace;font-size:34px;font-weight:700;letter-spacing:10px;color:#5a7339;background:#f4f2ec;border-radius:12px;padding:18px 12px;">
            <?= esc($code) ?>
        </div>
        <p style="margin:24px 0 0;color:#8a8c92;font-size:13px;">Si no pediste este codigo, ignora este correo.</p>
    </div>
</body>
</html>
