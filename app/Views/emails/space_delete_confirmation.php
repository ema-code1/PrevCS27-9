<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar espacio</title>
</head>
<body style="margin:0;padding:0;background-color:#FBFAF7;font-family:'Segoe UI',Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FBFAF7;padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(42,44,50,0.10);">
                    <tr>
                        <td style="background-color:#7A271A;padding:0;line-height:0;">
                            <img src="https://i.imgur.com/L2kb5bQ.png" alt="Preventive Control System" width="600" style="display:block;width:100%;max-width:600px;height:auto;border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#FFFFFF;padding:40px 48px 36px;">
                            <p style="margin:0 0 8px;font-size:22px;font-weight:600;color:#2A2C32;line-height:1.3;">Confirmar eliminación</p>
                            <p style="margin:0 0 24px;font-size:15px;color:#4A4D55;line-height:1.6;">
                                Se pidió eliminar <strong><?= esc($spaceName) ?></strong>.
                                El enlace abre la confirmación final.
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 28px;">
                                <tr>
                                    <td style="border-radius:8px;background-color:#B42318;">
                                        <a href="<?= esc($deleteLink, 'attr') ?>" target="_blank" style="display:inline-block;padding:14px 32px;font-size:15px;font-weight:600;color:#FFFFFF;text-decoration:none;">
                                            Confirmar eliminación
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0;font-size:13px;color:#6B6D72;line-height:1.5;">Este enlace expira en 15 minutos.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
