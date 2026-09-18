<?php

namespace App\Libraries;

use App\Models\IndustryUserModel;
use App\Models\NotificationModel;
use App\Models\SpaceEventAssigneeModel;
use App\Models\SpaceEventModel;
use Config\Database;
use DateTimeImmutable;

/**
 * Recordatorios del calendario del espacio.
 *
 * Cada evento guarda CUÁNDO avisar (space_events.notify) y el momento exacto
 * ya resuelto (notify_at). Esta clase resuelve ese momento al guardar y
 * reparte los avisos vencidos con flushDue().
 *
 * No hay demonio ni cron obligatorio: flushDue() corre en cada lectura del
 * feed de la campana (NotificationController::feed, que el layout consulta
 * cada minuto), así que el aviso llega con un minuto de retraso como máximo
 * mientras alguien tenga la app abierta. Para que salgan igual con todos
 * desconectados está el comando "php spark calendario:recordatorios", que
 * llama a lo mismo y se puede colgar del cron o del Programador de tareas.
 *
 * El reparto es a los encargados del evento (los del selector "Encargado(s)")
 * y, si no quedó ninguno, a quien lo creó: un recordatorio sin destinatario no
 * le sirve a nadie.
 */
final class SpaceEventReminders
{
    /** Clave que guarda un evento sin recordatorio. */
    public const NONE = 'none';

    /**
     * Opciones del selector "Notificaciones", en el orden en que se muestran.
     * La clave es lo que viaja y se guarda; el valor, la etiqueta visible.
     */
    public const OPTIONS = [
        self::NONE    => 'No notificar',
        'day_before'  => 'El día anterior',
        'same_day'    => 'Mismo día a primera hora',
        'hour_before' => '1 hora antes',
        'ten_min'     => '10 minutos antes',
    ];

    /**
     * "Primera hora" de la planta: la hora a la que caen los avisos del día
     * anterior y del mismo día. A las 8 ya hay alguien para leerlos.
     */
    public const FIRST_HOUR = 8;

    /** Cuántos recordatorios se reparten por barrido. */
    private const BATCH = 200;

    /**
     * "Ahora" en la misma hora de pared que guardan los eventos.
     *
     * start_at / end_at se guardan sin zona: son la hora que eligió quien
     * cargó el evento en el navegador. La app corre con appTimezone = UTC, así
     * que el reloj de PHP está corrido respecto de esa hora y no sirve para
     * compararla. El de la base sí: es el mismo NOW() que ya escribe
     * created_at / updated_at.
     */
    public static function now(): DateTimeImmutable
    {
        $row  = Database::connect()->query('SELECT NOW() AS wall_clock')->getRowArray();
        $wall = $row['wall_clock'] ?? null;

        return new DateTimeImmutable(is_string($wall) && $wall !== '' ? $wall : 'now');
    }

    public static function isValid(string $key): bool
    {
        return array_key_exists($key, self::OPTIONS);
    }

    /** Clave saneada: lo que no está en la lista no notifica. */
    public static function normalize($key): string
    {
        $key = is_string($key) ? $key : '';

        return self::isValid($key) ? $key : self::NONE;
    }

    /** Opciones con la forma que consume el JS del calendario. */
    public static function optionList(): array
    {
        $out = [];

        foreach (self::OPTIONS as $value => $label) {
            $out[] = ['value' => $value, 'label' => $label];
        }

        return $out;
    }

    /**
     * Momento exacto del aviso, o null si el evento no notifica.
     *
     * Los avisos "de primera hora" que caerían después del arranque del
     * evento se corren al arranque: avisar a las 8 de algo que empezó a las 7
     * llega tarde por definición.
     */
    public static function at(DateTimeImmutable $start, string $key): ?DateTimeImmutable
    {
        $key = self::normalize($key);

        $when = match ($key) {
            'day_before'  => $start->modify('-1 day')->setTime(self::FIRST_HOUR, 0),
            'same_day'    => $start->setTime(self::FIRST_HOUR, 0),
            'hour_before' => $start->modify('-1 hour'),
            'ten_min'     => $start->modify('-10 minutes'),
            default       => null,
        };

        if ($when === null) {
            return null;
        }

        return $when > $start ? $start : $when;
    }

    /**
     * Columnas de recordatorio listas para guardar.
     *
     * notified_at se resuelve acá y no en el barrido: si el momento del aviso
     * ya pasó y el evento también, se marca como repartido para no avisar de
     * algo que terminó. Si el aviso venció pero el evento todavía no arrancó
     * (crear a las 14:55 algo de las 15:00 con "10 minutos antes"), queda
     * pendiente y el próximo barrido lo manda enseguida, que es lo que espera
     * quien lo cargó.
     */
    public static function columnsFor(DateTimeImmutable $start, string $key, ?DateTimeImmutable $now = null): array
    {
        $now      = $now ?? self::now();
        $key      = self::normalize($key);
        $notifyAt = self::at($start, $key);

        if ($notifyAt === null) {
            return ['notify' => $key, 'notify_at' => null, 'notified_at' => null];
        }

        return [
            'notify'      => $key,
            'notify_at'   => $notifyAt->format('Y-m-d H:i:s'),
            'notified_at' => $start <= $now ? $now->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * Igual que columnsFor(), pero para un evento que ya existe: si el momento
     * del aviso no se movió, conserva el notified_at que tenía.
     *
     * Sin esto, cambiarle el título a un evento cuyo recordatorio ya salió
     * (el de "el día anterior", por ejemplo) lo volvería a repartir.
     */
    public static function columnsForUpdate(array $event, DateTimeImmutable $start, string $key, ?DateTimeImmutable $now = null): array
    {
        $columns = self::columnsFor($start, $key, $now);

        $sameMoment = ($event['notify'] ?? self::NONE) === $columns['notify']
            && ($event['notify_at'] ?? null) === $columns['notify_at'];

        if ($sameMoment && ($event['notified_at'] ?? null) !== null) {
            $columns['notified_at'] = $event['notified_at'];
        }

        return $columns;
    }

    /**
     * Reparte los recordatorios vencidos. Devuelve cuántos eventos avisó.
     *
     * Cada evento se "toma" con un UPDATE condicionado a notified_at IS NULL:
     * si dos pestañas barren a la vez, solo una de las dos escribe la fila y
     * la otra ve 0 filas afectadas, así que el aviso no sale duplicado.
     */
    public static function flushDue(?DateTimeImmutable $now = null): int
    {
        $now   = $now ?? self::now();
        $stamp = $now->format('Y-m-d H:i:s');
        $db    = Database::connect();

        $due = $db->table('space_events')
            ->select('id, industry_id, title, category, start_at, notify, created_by')
            ->where('notified_at', null)
            ->where('notify !=', self::NONE)
            ->where('notify_at IS NOT NULL', null, false)
            ->where('notify_at <=', $stamp)
            ->orderBy('notify_at', 'ASC')
            ->limit(self::BATCH)
            ->get()
            ->getResultArray();

        if ($due === []) {
            return 0;
        }

        $assignees     = new SpaceEventAssigneeModel();
        $notifications = new NotificationModel();
        $memberships   = new IndustryUserModel();
        $membersCache  = [];
        $sent          = 0;

        foreach ($due as $event) {
            $eventId    = (int) $event['id'];
            $industryId = (int) $event['industry_id'];

            $db->table('space_events')
                ->where('id', $eventId)
                ->where('notified_at', null)
                ->update(['notified_at' => $stamp]);

            if ($db->affectedRows() < 1) {
                continue;
            }

            if (! isset($membersCache[$industryId])) {
                $membersCache[$industryId] = array_map(
                    static fn (array $member): int => (int) $member['user_id'],
                    $memberships->membersForIndustry($industryId)
                );
            }

            // Un encargado que ya no es miembro del espacio no recibe nada:
            // perdió el acceso al calendario que originó el aviso.
            $targets = array_values(array_intersect(
                $assignees->userIdsFor($eventId),
                $membersCache[$industryId]
            ));

            if ($targets === [] && $event['created_by'] !== null) {
                $targets = array_values(array_intersect(
                    [(int) $event['created_by']],
                    $membersCache[$industryId]
                ));
            }

            if ($targets === []) {
                continue;
            }

            $notifications->sendTo($industryId, $targets, [
                'level'      => NotificationModel::LEVEL_INFO,
                'title'      => 'Recordatorio: ' . $event['title'],
                'text'       => self::text($event, $now),
                'source'     => NotificationModel::SOURCE_CALENDAR,
                'source_key' => 'calendar:' . $eventId,
                'link'       => 'espacios/' . $industryId . '/stock#calendario',
            ]);

            $sent++;
        }

        return $sent;
    }

    /**
     * Cuerpo del aviso: de qué se trata y cuándo arranca.
     * "Mantenimiento · Empieza hoy a las 15:00."
     */
    private static function text(array $event, DateTimeImmutable $now): string
    {
        $start = strtotime((string) $event['start_at']);
        if ($start === false) {
            return 'Recordatorio del calendario del espacio.';
        }

        $days = (int) $now->setTime(0, 0)
            ->diff(new DateTimeImmutable(date('Y-m-d', $start)))
            ->format('%r%a');

        $day = match ($days) {
            0       => 'hoy',
            1       => 'mañana',
            default => 'el ' . date('d/m', $start),
        };

        $when     = 'Empieza ' . $day . ' a las ' . date('H:i', $start) . '.';
        $category = trim((string) ($event['category'] ?? ''));

        return $category !== '' ? $category . ' · ' . $when : $when;
    }

    /** Etiquetas fijas que tienen todos los espacios. */
    public static function defaultTags(): array
    {
        return SpaceEventModel::TAGS;
    }
}
