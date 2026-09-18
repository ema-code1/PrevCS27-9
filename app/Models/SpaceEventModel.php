<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Eventos del calendario de un espacio (pestaña "Calendario" de la vista de
 * stock). Todo acceso por id pasa por findInIndustry(), que exige que el
 * evento sea del espacio de la URL.
 *
 * Las etiquetas son dos listas que se suman: TAGS, fija para todos los
 * espacios, y las propias de cada espacio (space_event_tags). El recordatorio
 * (notify / notify_at / notified_at) lo resuelve App\Libraries\SpaceEventReminders.
 */
class SpaceEventModel extends Model
{
    public const COLORS = ['blue', 'green', 'purple', 'orange', 'pink', 'red'];

    public const CATEGORIES = ['Mantenimiento', 'Inspección', 'Reunión', 'Recordatorio'];

    public const TAGS = ['Urgente', 'Importante', 'Eléctrico', 'Mecánico', 'Seguridad', 'Proveedor'];

    protected $table            = 'space_events';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'industry_id', 'title', 'description', 'start_at', 'end_at',
        'color', 'category', 'tags', 'notify', 'notify_at', 'notified_at', 'created_by',
    ];

    protected bool $allowEmptyInserts = false;

    public function findInIndustry(int $eventId, int $industryId): ?array
    {
        return $this->where('id', $eventId)
            ->where('industry_id', $industryId)
            ->first();
    }

    /** Todos los eventos del espacio, listos para el JS. */
    public function payloadForIndustry(int $industryId): array
    {
        $rows = $this->where('industry_id', $industryId)
            ->orderBy('start_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        // Los encargados salen en una sola consulta para todo el espacio.
        $assignees = (new SpaceEventAssigneeModel())->idsByEvent($industryId);

        return array_map(
            fn (array $row): array => $this->toPayload($row, $assignees[(int) $row['id']] ?? null),
            $rows
        );
    }

    public function payload(int $eventId): ?array
    {
        $row = $this->find($eventId);

        return $row !== null ? $this->toPayload($row) : null;
    }

    /**
     * Saca una etiqueta de todos los eventos del espacio. Se usa al borrar una
     * etiqueta propia: dejarla pegada en el JSON la mostraría en eventos
     * viejos sin poder volver a elegirla.
     */
    public function stripTag(int $industryId, string $tag): void
    {
        $rows = $this->select('id, tags')
            ->where('industry_id', $industryId)
            ->like('tags', $tag)
            ->findAll();

        foreach ($rows as $row) {
            $tags = json_decode((string) ($row['tags'] ?? ''), true);
            if (! is_array($tags) || ! in_array($tag, $tags, true)) {
                continue;
            }

            $tags = array_values(array_filter($tags, static fn ($value): bool => $value !== $tag));
            $this->update((int) $row['id'], ['tags' => json_encode($tags, JSON_UNESCAPED_UNICODE)]);
        }
    }

    /**
     * Fechas en formato "YYYY-MM-DDTHH:MM" sin zona: el navegador las lee como
     * hora local, que es la misma que eligió quien cargó el evento.
     *
     * Los encargados viajan como ids: el JS les pone nombre y avatar con la
     * lista de miembros del espacio, que ya tiene.
     */
    private function toPayload(array $row, ?array $assignees = null): array
    {
        $tags = json_decode((string) ($row['tags'] ?? ''), true);

        if ($assignees === null) {
            $assignees = (new SpaceEventAssigneeModel())->userIdsFor((int) $row['id']);
        }

        return [
            'id'          => (string) $row['id'],
            'title'       => $row['title'],
            'description' => $row['description'] ?? '',
            'startTime'   => date('Y-m-d\TH:i', strtotime($row['start_at'])),
            'endTime'     => date('Y-m-d\TH:i', strtotime($row['end_at'])),
            'color'       => $row['color'],
            'category'    => $row['category'] ?? '',
            'tags'        => is_array($tags) ? array_values($tags) : [],
            'assignees'   => array_values(array_map('intval', $assignees)),
            'notify'      => (string) ($row['notify'] ?? 'none'),
        ];
    }
}
