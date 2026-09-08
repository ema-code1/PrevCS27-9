<?php
/**
 * Lista de sensores del modal "Configuración de la placa".
 *
 * Vive aparte porque es una región que el servidor vuelve a dibujar sola:
 * agregar, cambiar o borrar un sensor contesta con este mismo HTML y el
 * navegador lo reemplaza en su lugar, sin recargar la página ni cerrar la
 * ventana. La primera carga la incluye detalle.php con los mismos datos.
 *
 * @var array $device
 * @var array $sensors
 * @var array $availableTypes  Tipos libres (alta de un sensor nuevo).
 * @var array $allTypes        Todos los tipos (el "Cambiar" de cada fila).
 * @var array $usedTypeIds
 * @var bool  $isAdmin
 */
$canAdd = $isAdmin && $availableTypes !== [];
?>
<div class="space-modal__section-head">
    <h4>Sensores <span class="space-modal__count"><?= count($sensors) ?></span></h4>
    <?php if ($canAdd): ?>
        <button type="button" class="esp32-icon-btn" data-add-toggle
                aria-expanded="false" aria-controls="esp32-add-sensor"
                aria-label="Agregar sensor" title="Agregar sensor">
            <!-- Heroicons mini · plus -->
            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/></svg>
        </button>
    <?php endif; ?>
</div>

<?php if ($canAdd): ?>
    <div class="esp-collapse" id="esp32-add-sensor" data-add-panel>
        <div class="esp-collapse__inner">
            <form action="<?= base_url('esp32/' . $device['id'] . '/sensores') ?>" method="post"
                  class="esp32-sensor-form esp32-sensor-form--modal" data-ajax>
                <?= csrf_field() ?>
                <select name="sensor_type_id" class="esp32-select" required>
                    <option value="" disabled selected>Tipo de sensor</option>
                    <?php foreach ($availableTypes as $t): ?>
                        <option value="<?= esc($t['id']) ?>"><?= esc($t['name']) ?><?= !empty($t['unit']) ? ' (' . esc($t['unit']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="label" class="esp32-input" placeholder="Etiqueta (opcional)" maxlength="150">
                <button type="submit" class="btn btn--primary">Añadir</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($sensors === []): ?>
    <p class="text-muted">Esta placa todavía no tiene sensores configurados.</p>
<?php else: ?>
    <div class="esp32-sensor-list">
        <?php foreach ($sensors as $s): ?>
            <?php $sensorTitle = $s['label'] ?: $s['name']; ?>
            <div class="esp32-sensor-item" data-sensor-item data-sensor-id="<?= (int) $s['id'] ?>">
                <div class="esp32-sensor-row">
                    <div class="esp32-sensor-row__info">
                        <strong><?= esc($sensorTitle) ?></strong>
                        <small><?= esc($s['name']) ?><?= !empty($s['unit']) ? ' · ' . esc($s['unit']) : '' ?></small>
                    </div>
                    <?php if ($isAdmin): ?>
                        <div class="iot-kebab" data-kebab>
                            <button type="button" class="iot-kebab__button iot-kebab__button--icon"
                                    aria-label="Opciones de <?= esc($sensorTitle) ?>" aria-expanded="false" data-kebab-button>
                                <!-- Heroicons mini · ellipsis-vertical -->
                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 3a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM10 8.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM11.5 15.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0Z"/></svg>
                            </button>
                            <div class="iot-kebab__menu" hidden data-kebab-menu>
                                <button type="button" class="iot-kebab__option" data-sensor-edit>
                                    <!-- Heroicons mini · pencil-square -->
                                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z"/><path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z"/></svg>
                                    Cambiar
                                </button>
                                <form action="<?= base_url('esp32/' . $device['id'] . '/sensores/' . $s['id'] . '/eliminar') ?>" method="post"
                                      data-confirm="¿Borrar el sensor «<?= esc($sensorTitle) ?>»? Se van también todas sus lecturas."
                                      class="iot-kebab__form--sep" data-ajax>
                                    <?= csrf_field() ?>
                                    <button type="submit" class="iot-kebab__option iot-kebab__option--danger">
                                        <!-- Heroicons mini · trash -->
                                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd"/></svg>
                                        Borrar
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($isAdmin): ?>
                    <?php /* "Cambiar": el mismo par tipo + etiqueta del alta, ya cargado con
                            lo que tiene el sensor. Los tipos tomados por otro sensor de la
                            placa no se ofrecen (el UNIQUE los rechazaría). */ ?>
                    <div class="esp-collapse" data-sensor-edit-panel>
                        <div class="esp-collapse__inner">
                            <form action="<?= base_url('esp32/' . $device['id'] . '/sensores/' . $s['id']) ?>" method="post"
                                  class="esp32-sensor-edit" data-ajax>
                                <?= csrf_field() ?>
                                <select name="sensor_type_id" class="esp32-select" required>
                                    <?php foreach ($allTypes as $t): ?>
                                        <?php $isOwn = (int) $t['id'] === (int) $s['sensor_type_id']; ?>
                                        <?php if (! $isOwn && in_array((int) $t['id'], $usedTypeIds, true)) continue; ?>
                                        <option value="<?= esc($t['id']) ?>"<?= $isOwn ? ' selected' : '' ?>><?= esc($t['name']) ?><?= !empty($t['unit']) ? ' (' . esc($t['unit']) . ')' : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="label" class="esp32-input" placeholder="Etiqueta (opcional)" maxlength="150" value="<?= esc($s['label'] ?? '') ?>">
                                <div class="esp32-sensor-edit__actions">
                                    <button type="button" class="btn btn--secondary btn--sm" data-sensor-edit-cancel>Cancelar</button>
                                    <button type="submit" class="btn btn--primary btn--sm">Guardar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
