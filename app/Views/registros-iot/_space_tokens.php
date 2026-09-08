<?php
/**
 * Tokens de las ESP y sensores del espacio: se desbloquean con la clave y
 * quedan a la vista cinco minutos.
 *
 * Parcial por el mismo motivo que la de miembros: desbloquear y borrar un
 * sensor se mandan por fetch y la respuesta trae este HTML ya actualizado
 * para reemplazarlo en su lugar, sin recargar la página.
 *
 * Variables: $ind, $isUnlocked, $unlockedTokens.
 */
?>
<h4>Tokens y sensores</h4>
<?php if (! $isUnlocked): ?>
    <p class="text-muted">Ingresá la clave del espacio para ver los tokens de las ESP y poder borrar sensores.</p>
    <form action="<?= base_url('espacios/' . $ind['id'] . '/tokens') ?>" method="post" class="space-modal__unlock" data-ajax>
        <?= csrf_field() ?>
        <input type="password" name="space_password" placeholder="Clave del espacio" required>
        <button type="submit" class="btn btn--primary">Desbloquear</button>
    </form>
<?php else: ?>
    <p class="space-modal__unlocked">Desbloqueado por 5 minutos. Tokens visibles y sensores borrables.</p>

    <div class="space-modal__group">
        <strong>Tokens de las ESP</strong>
        <?php if (($ind['devices'] ?? []) === []): ?>
            <p class="text-muted">Este espacio no tiene ESP vinculadas.</p>
        <?php else: ?>
            <?php foreach ($ind['devices'] as $device): ?>
                <?php $tk = $unlockedTokens[$device['id']] ?? null; ?>
                <div class="space-modal__token-row">
                    <span><?= esc($device['name']) ?></span>
                    <code class="esp32-token"><?= $tk ? esc($tk) : 'sin token' ?></code>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="space-modal__group">
        <strong>Sensores del espacio</strong>
        <?php if (($ind['sensors'] ?? []) === []): ?>
            <p class="text-muted">No hay sensores en este espacio.</p>
        <?php else: ?>
            <?php foreach ($ind['sensors'] as $sensor): ?>
                <div class="iot-space-member">
                    <div class="iot-space-member__info">
                        <strong><?= esc($sensor['label'] ?: $sensor['name']) ?></strong>
                        <small><?= esc($sensor['device_name']) ?> · <?= esc($sensor['name']) ?><?= ! empty($sensor['unit']) ? ' (' . esc($sensor['unit']) . ')' : '' ?></small>
                    </div>
                    <form action="<?= base_url('espacios/' . $ind['id'] . '/sensores/' . $sensor['id'] . '/eliminar') ?>" method="post"
                          data-confirm="¿Borrar el sensor &quot;<?= esc($sensor['label'] ?: $sensor['name']) ?>&quot;? Se eliminan también todas sus lecturas."
                          data-ajax>
                        <?= csrf_field() ?>
                        <button type="submit" class="iot-danger-link">Borrar</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>
