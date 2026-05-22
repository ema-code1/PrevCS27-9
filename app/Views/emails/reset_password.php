<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de contraseña</title>
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
                            <?php /* base_url('images/email_password_recover/prevcs-lockup-ivory-1200x300.png') */ ?>
                            <img src="https://i.imgur.com/L2kb5bQ.png"
                                 alt="Prevention Control System"
                                 width="600"
                                 style="display:block;width:100%;max-width:600px;height:auto;border:0;">
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="background-color:#FFFFFF;padding:40px 48px 36px;">

                            <p style="margin:0 0 8px;font-size:22px;font-weight:600;color:#2A2C32;line-height:1.3;">
                                Hola,
                            </p>

                            <p style="margin:0 0 24px;font-size:15px;color:#4A4D55;line-height:1.6;">
                                Recibimos una solicitud para restablecer la contraseña de tu cuenta.<br>
                                Hacé clic en el botón para continuar:
                            </p>

                            <!-- CTA button -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                   style="margin:0 0 28px;">
                                <tr>
                                    <td style="border-radius:8px;background-color:#4A6830;">
                                        <a href="<?= esc($resetLink, 'attr') ?>"
                                           target="_blank"
                                           style="display:inline-block;padding:14px 32px;font-size:15px;font-weight:600;color:#F2F1EC;text-decoration:none;letter-spacing:0.02em;">
                                            Restablecer contraseña
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 6px;font-size:13px;color:#6B6D72;line-height:1.5;">
                                Este enlace expira en <strong>15 minutos</strong>.
                            </p>
                            <p style="margin:0;font-size:13px;color:#6B6D72;line-height:1.5;">
                                Si no solicitaste este cambio, podés ignorar este correo.
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#F4F2EC;padding:20px 48px;border-top:1px solid #E8E6DF;">
                            <p style="margin:0;font-size:12px;color:#9A9CA2;text-align:center;line-height:1.5;">
                                Prevention Control System &mdash; Este es un mensaje automático, no respondas este correo.
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
