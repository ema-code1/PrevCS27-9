<?php
/**
 * Invitación a un espacio.
 *
 * Mismo lenguaje visual que los otros correos (banner verde + tarjeta ivory).
 * La imagen del banner se sirve hosteada y no desde base_url() porque los
 * clientes de correo no pueden cargar localhost.
 *
 * Variables: $spaceName, $companyName, $sector, $inviterName, $roleLabel,
 * $permissionLabel, $email, $acceptLink, $expiresLabel.
 */
$permissionLabel = $permissionLabel ?? '';
$sector          = trim((string) ($sector ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Te invitaron a un espacio</title>
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
                        <td style="background-color:#FFFFFF;padding:40px 48px 36px;">

                            <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#5A7339;text-transform:uppercase;letter-spacing:.08em;">
                                Invitación a un espacio
                            </p>

                            <h1 style="margin:0 0 16px;font-size:24px;font-weight:600;color:#2A2C32;line-height:1.3;">
                                <?= esc($inviterName) ?> te invitó a <?= esc($spaceName) ?>
                            </h1>

                            <p style="margin:0 0 24px;font-size:15px;color:#4A4D55;line-height:1.6;">
                                Un espacio es el lugar de Preventive Control System donde una planta lleva
                                sus placas ESP32, los registros de sus sensores, el stock y el tablero de
                                tareas. Al aceptar, ese espacio te aparece en tu cuenta junto a los tuyos.
                            </p>

                            <!-- Ficha del espacio -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                   style="margin:0 0 28px;background-color:#F4F2EC;border:1px solid #E8E6DF;border-radius:12px;">
                                <tr>
                                    <td style="padding:20px 24px;">
                                        <p style="margin:0 0 4px;font-size:12px;font-weight:700;color:#8A8C92;text-transform:uppercase;letter-spacing:.07em;">
                                            Espacio
                                        </p>
                                        <p style="margin:0 0 14px;font-size:17px;font-weight:600;color:#2A2C32;line-height:1.4;">
                                            <?= esc($spaceName) ?>
                                        </p>

                                        <p style="margin:0 0 4px;font-size:12px;font-weight:700;color:#8A8C92;text-transform:uppercase;letter-spacing:.07em;">
                                            Empresa
                                        </p>
                                        <p style="margin:0 0 14px;font-size:15px;color:#4A4D55;line-height:1.4;">
                                            <?= esc($companyName) ?><?= $sector !== '' ? ' &middot; ' . esc($sector) : '' ?>
                                        </p>

                                        <p style="margin:0 0 4px;font-size:12px;font-weight:700;color:#8A8C92;text-transform:uppercase;letter-spacing:.07em;">
                                            Tu acceso
                                        </p>
                                        <p style="margin:0;font-size:15px;color:#4A4D55;line-height:1.4;">
                                            <?= esc($roleLabel) ?><?= $permissionLabel !== '' ? ' &middot; ' . esc($permissionLabel) : '' ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA button -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                   style="margin:0 0 28px;">
                                <tr>
                                    <td style="border-radius:8px;background-color:#4A6830;">
                                        <a href="<?= esc($acceptLink, 'attr') ?>"
                                           target="_blank"
                                           style="display:inline-block;padding:14px 32px;font-size:15px;font-weight:600;color:#F2F1EC;text-decoration:none;letter-spacing:0.02em;">
                                            Aceptar invitación
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 6px;font-size:13px;color:#6B6D72;line-height:1.5;">
                                La invitación es para <strong><?= esc($email) ?></strong>: entrá con esa cuenta.
                                Si todavía no tenés una, creala con ese mismo correo y volvé a abrir el enlace.
                            </p>
                            <p style="margin:0 0 6px;font-size:13px;color:#6B6D72;line-height:1.5;">
                                El enlace vence el <strong><?= esc($expiresLabel) ?></strong> y sirve una sola vez.
                            </p>
                            <p style="margin:0;font-size:13px;color:#6B6D72;line-height:1.5;">
                                Si no esperabas esta invitación, podés ignorar este correo: sin abrir el enlace
                                nadie entra a tu cuenta ni vos al espacio.
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
