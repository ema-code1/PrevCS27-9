<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isTwoFactor ? 'Codigo de acceso' : 'Verifica tu cuenta' ?></title>
</head>
<body style="margin:0;padding:0;background-color:#FBFAF7;font-family:'Segoe UI',Arial,sans-serif;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="background-color:#FBFAF7;padding:32px 0;">
        <tr>
            <td align="center">

                <!-- Card wrapper -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
                       style="max-width:600px;width:100%;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(42,44,50,0.10);">

                    <!-- Banner header -->
                    <tr>
                        <td style="background-color:#3D5C26;padding:0;line-height:0;">
                            <?php /* base_url('images/email_password_recover/prevcs-lockup-ivory-1200x300.png') — los clientes de correo no cargan localhost, se usa la copia hosteada */ ?>
                            <img src="https://i.imgur.com/L2kb5bQ.png"
                                 alt="Preventive Control System"
                                 width="600"
                                 style="display:block;width:100%;max-width:600px;height:auto;border:0;">
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="background-color:#FFFFFF;padding:40px 48px 36px;text-align:center;">

                            <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#5A7339;text-transform:uppercase;letter-spacing:.08em;">
                                <?= $isTwoFactor ? 'Verificacion en dos pasos' : 'Verificacion de cuenta' ?>
                            </p>

                            <h1 style="margin:0 0 12px;font-size:24px;font-weight:600;color:#2A2C32;line-height:1.3;">
                                <?= $isTwoFactor ? 'Confirma tu inicio de sesion' : 'Verifica tu correo' ?>
                            </h1>

                            <p style="margin:0 0 28px;font-size:15px;color:#4A4D55;line-height:1.6;">
                                Hola <?= esc($userName !== '' ? $userName : 'usuario') ?>. Usa este codigo para continuar.<br>
                                Vence en <strong>5 minutos</strong>.
                            </p>

                            <!-- Codigo -->
                            <div style="font-family:'Courier New',monospace;font-size:34px;font-weight:700;letter-spacing:10px;color:#4A6830;background:#F4F2EC;border:1px solid #E8E6DF;border-radius:12px;padding:18px 12px;">
                                <?= esc($code) ?>
                            </div>

                            <p style="margin:28px 0 0;font-size:13px;color:#8A8C92;line-height:1.5;">
                                Si no <?= $isTwoFactor ? 'intentaste iniciar sesion' : 'creaste esta cuenta' ?>, ignora este correo.
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#F4F2EC;padding:20px 48px;border-top:1px solid #E8E6DF;">
                            <p style="margin:0;font-size:12px;color:#9A9CA2;text-align:center;line-height:1.5;">
                                Preventive Control System &mdash; Este es un mensaje automático, no respondas este correo.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- /Card wrapper -->

            </td>
        </tr>
    </table>

</body>
</html>
