<?php
/**
 * Personas con acceso a un espacio: invitar, listar y administrar.
 *
 * Es una parcial y no marcado suelto adentro del modal porque el servidor la
 * vuelve a dibujar sola: cada acción de esta sección se manda por fetch y la
 * respuesta trae este mismo HTML ya actualizado, que el navegador reemplaza
 * en su lugar. Así la lista siempre sale del mismo lado y no hay una copia
 * en JavaScript que se pueda desincronizar de la base.
 *
 * Variables: $ind (espacio con members e invitations), $sessionUserId.
 */
$members     = $ind['members'] ?? [];
$invitations = $ind['invitations'] ?? [];
?>
<div class="space-modal__section-head">
    <h4>Personas con acceso <span class="space-modal__count"><?= count($members) ?></span></h4>
    <?php /* Invitar por mail, como compartir un documento: se despliega el
            campo, sale el correo con el link y la persona entra sola. */ ?>
    <button type="button" class="space-ghost-btn space-ghost-btn--compact"
            data-disclosure="space-invite-<?= $ind['id'] ?>"
            aria-expanded="false" aria-controls="space-invite-<?= $ind['id'] ?>">
        <!-- Heroicons mini · user-plus -->
        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 0 0-13.074.003ZM17 4.5a.75.75 0 0 0-1.5 0v1h-1a.75.75 0 0 0 0 1.5h1v1a.75.75 0 0 0 1.5 0V7h1a.75.75 0 0 0 0-1.5h-1v-1Z"/></svg>
        Invitar
    </button>
</div>

<form class="space-invite" id="space-invite-<?= $ind['id'] ?>" data-disclosure-panel hidden
      action="<?= base_url('espacios/' . $ind['id'] . '/invitaciones') ?>" method="post" data-ajax>
    <?= csrf_field() ?>
    <label class="auth-field">
        <span class="auth-field__label">E-mail</span>
        <input type="email" name="email" placeholder="nombre@empresa.com" required autocomplete="off">
    </label>
    <div class="space-invite__row">
        <label class="auth-field">
            <span class="auth-field__label">Rol</span>
            <select name="role" data-pcs-select data-invite-role>
                <option value="supervisor" selected>Supervisor</option>
                <option value="admin_principal">Admin</option>
            </select>
        </label>
        <label class="auth-field" data-invite-permission>
            <span class="auth-field__label">Permiso</span>
            <select name="permission" data-pcs-select>
                <option value="read" selected>Solo lectura</option>
                <option value="edit">Puede editar</option>
            </select>
        </label>
    </div>
    <button type="submit" class="btn btn--primary">Enviar invitación</button>
</form>

<div class="iot-space-members">
    <?php foreach ($members as $member): ?>
        <?php
        $memberName       = trim(($member['nombre'] ?? '') . ' ' . ($member['apellido'] ?? '')) ?: $member['email'];
        $memberPermission = $member['permission'] ?? 'read';
        $memberIsAdmin    = $member['role'] === 'admin_principal';
        // El propio acceso no se toca desde acá (ni el rol ni la baja):
        // quedarse sin admin dejaría el espacio sin nadie que lo configure.
        $isSelf           = (int) $member['user_id'] === $sessionUserId;
        $roleUrl          = base_url('espacios/' . $ind['id'] . '/miembros/' . $member['id'] . '/rol');
        ?>
        <div class="iot-space-member">
            <div class="iot-space-member__info">
                <strong><?= esc($memberName) ?></strong>
                <small><?= esc($member['email']) ?></small>
            </div>
            <span class="space-tag<?= $memberIsAdmin ? ' space-tag--admin' : '' ?>">
                <?= $memberIsAdmin ? 'Admin' : 'Supervisor' ?>
            </span>
            <?php if (! $isSelf): ?>
                <div class="iot-kebab" data-kebab>
                    <button type="button" class="iot-kebab__button" aria-label="Opciones de <?= esc($memberName) ?>" aria-expanded="false" data-kebab-button>
                        <span></span><span></span><span></span>
                    </button>
                    <div class="iot-kebab__menu" hidden data-kebab-menu>
                        <p class="iot-kebab__label">Rol</p>

                        <form action="<?= esc($roleUrl, 'attr') ?>" method="post" data-ajax>
                            <?= csrf_field() ?>
                            <input type="hidden" name="role" value="admin_principal">
                            <button type="submit" class="iot-kebab__option<?= $memberIsAdmin ? ' is-active' : '' ?>">
                                <!-- Heroicons mini · shield-check -->
                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.661 2.237a.531.531 0 0 1 .678 0 11.947 11.947 0 0 0 7.078 2.749.5.5 0 0 1 .479.425c.069.52.104 1.05.104 1.589 0 5.162-3.26 9.563-7.834 11.256a.48.48 0 0 1-.332 0C5.26 16.564 2 12.163 2 7c0-.538.035-1.069.104-1.589a.5.5 0 0 1 .48-.425 11.947 11.947 0 0 0 7.077-2.75Zm4.196 5.954a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                                Admin
                            </button>
                        </form>

                        <?php /* Supervisor abre al costado, como un submenú de Windows: el
                                permiso no es otra decisión suelta sino la forma de ser
                                supervisor, así que se elige en el mismo gesto. */ ?>
                        <div class="iot-kebab__sub" data-kebab-sub>
                            <button type="button" class="iot-kebab__option iot-kebab__option--parent<?= $memberIsAdmin ? '' : ' is-active' ?>"
                                    aria-haspopup="true" aria-expanded="false" data-kebab-sub-button>
                                <!-- Heroicons mini · user -->
                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-7 9a7 7 0 1 1 14 0H3Z" clip-rule="evenodd"/></svg>
                                <span>Supervisor</span>
                                <svg class="iot-kebab__chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                            </button>
                            <div class="iot-kebab__flyout" data-kebab-flyout>
                                <form action="<?= esc($roleUrl, 'attr') ?>" method="post" data-ajax>
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="role" value="supervisor">
                                    <input type="hidden" name="permission" value="read">
                                    <button type="submit" class="iot-kebab__option<?= ! $memberIsAdmin && $memberPermission === 'read' ? ' is-active' : '' ?>">
                                        <!-- Heroicons mini · eye -->
                                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/><path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd"/></svg>
                                        Solo lectura
                                    </button>
                                </form>
                                <form action="<?= esc($roleUrl, 'attr') ?>" method="post" data-ajax>
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="role" value="supervisor">
                                    <input type="hidden" name="permission" value="edit">
                                    <button type="submit" class="iot-kebab__option<?= ! $memberIsAdmin && $memberPermission === 'edit' ? ' is-active' : '' ?>">
                                        <!-- Heroicons mini · pencil -->
                                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z"/><path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z"/></svg>
                                        Puede editar
                                    </button>
                                </form>
                            </div>
                        </div>

                        <form action="<?= base_url('espacios/' . $ind['id'] . '/miembros/' . $member['id'] . '/eliminar') ?>" method="post"
                              data-confirm="¿Quitar a <?= esc($memberName) ?> del espacio? Va a perder el acceso a los registros."
                              class="iot-kebab__form--sep" data-ajax>
                            <?= csrf_field() ?>
                            <button type="submit" class="iot-kebab__option iot-kebab__option--danger">
                                <!-- Heroicons mini · trash -->
                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd"/></svg>
                                Eliminar usuario
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php /* Invitaciones mandadas y todavía sin aceptar: se pueden cancelar, y ahí
        el enlace del correo deja de servir. */ ?>
<?php if ($invitations !== []): ?>
    <div class="space-pending">
        <p class="space-pending__title">Invitaciones pendientes</p>
        <?php foreach ($invitations as $invitation): ?>
            <div class="iot-space-member">
                <div class="iot-space-member__info">
                    <strong><?= esc($invitation['email']) ?></strong>
                    <small>
                        <?= $invitation['role'] === 'admin_principal' ? 'Admin' : 'Supervisor' ?>
                        · enviada el <?= esc(date('d/m/Y', strtotime((string) $invitation['created_at']))) ?>
                    </small>
                </div>
                <span class="space-tag space-tag--pending">Sin aceptar</span>
                <form action="<?= base_url('espacios/' . $ind['id'] . '/invitaciones/' . $invitation['id'] . '/cancelar') ?>" method="post"
                      data-confirm="¿Cancelar la invitación de <?= esc($invitation['email']) ?>? El enlace que recibió deja de funcionar."
                      data-ajax>
                    <?= csrf_field() ?>
                    <button type="submit" class="iot-danger-link">Cancelar</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
