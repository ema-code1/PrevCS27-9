<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Tablero Kanban de una ESP32. Hay exactamente uno por placa.
 */
class KanbanBoardModel extends Model
{
    /** Paleta cerrada compartida por tablero, columnas, tareas y etiquetas. */
    public const COLORS = [
        'azul', 'celeste', 'turquesa', 'verde', 'amarillo',
        'naranja', 'rojo', 'rosa', 'violeta', 'gris',
    ];

    protected $table            = 'kanban_boards';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'esp32_device_id', 'name', 'description', 'background_color', 'task_seq', 'created_by',
    ];

    protected bool $allowEmptyInserts = false;

    /**
     * ¿La clave de color pertenece a la paleta? Evita guardar cualquier cosa
     * que después no tenga estilo definido en el CSS.
     */
    public static function isValidColor(?string $color): bool
    {
        return $color !== null && in_array($color, self::COLORS, true);
    }

    /**
     * Devuelve el tablero de la placa, creándolo la primera vez que alguien
     * abre la vista. Así el usuario nunca ve un "todavía no hay tablero".
     */
    public function forDevice(int $deviceId, ?int $userId = null): array
    {
        $board = $this->where('esp32_device_id', $deviceId)->first();
        if ($board !== null) {
            return $board;
        }

        $id = $this->insert([
            'esp32_device_id'  => $deviceId,
            'name'             => 'Tareas de mantenimiento',
            'description'      => null,
            'background_color' => 'azul',
            'task_seq'         => 0,
            'created_by'       => $userId,
        ], true);

        // Si dos pestañas abren la placa a la vez, el unique de esp32_device_id
        // hace fallar al segundo insert: en ese caso releemos el que ganó.
        if ($id === false) {
            return $this->where('esp32_device_id', $deviceId)->first() ?? [];
        }

        return $this->find($id);
    }

    /**
     * Reserva el próximo identificador de tarea del tablero (#1, #2, ...).
     * El contador nunca retrocede, así que los códigos no se reutilizan.
     */
    public function nextTaskCode(int $boardId): string
    {
        $this->db->query(
            'UPDATE kanban_boards SET task_seq = task_seq + 1 WHERE id = ?',
            [$boardId]
        );

        $row = $this->select('task_seq')->find($boardId);

        return '#' . (int) ($row['task_seq'] ?? 1);
    }
}
