<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Eventos del calendario de un espacio (pestaña "Calendario" de la vista de
 * stock). Todo acceso por id pasa por findInIndustry(), que exige que el
 * evento sea del espacio de la URL.
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
        'color', 'category', 'tags', 'created_by',
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

        return array_map([$this, 'toPayload'], $rows);
    }

    public function payload(int $eventId): ?array
    {
        $row = $this->find($eventId);

        return $row !== null ? $this->toPayload($row) : null;
    }

    /**
     * Fechas en formato "YYYY-MM-DDTHH:MM" sin zona: el navegador las lee como
     * hora local, que es la misma que eligió quien cargó el evento.
     */
    private function toPayload(array $row): array
    {
        $tags = json_decode((string) ($row['tags'] ?? ''), true);

        return [
            'id'          => (string) $row['id'],
            'title'       => $row['title'],
            'description' => $row['description'] ?? '',
            'startTime'   => date('Y-m-d\TH:i', strtotime($row['start_at'])),
            'endTime'     => date('Y-m-d\TH:i', strtotime($row['end_at'])),
            'color'       => $row['color'],
            'category'    => $row['category'] ?? '',
            'tags'        => is_array($tags) ? array_values($tags) : [],
        ];
    }
}
