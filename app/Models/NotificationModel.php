<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Bandeja de notificaciones, una fila por destinatario.
 *
 * El fan-out se hace al escribir (una fila por miembro del espacio) en vez de
 * resolverlo al leer. Con equipos de este tamaño sale más barato, y deja el
 * "leído" y el contador de la campana como una query trivial por usuario.
 */
class NotificationModel extends Model
{
    public const SOURCE_STOCK = 'stock';

    /** Recordatorios del calendario del espacio (SpaceEventReminders). */
    public const SOURCE_CALENDAR = 'calendar';

    /** Lecturas de sensores fuera del rango configurado (SensorAlerts). */
    public const SOURCE_SENSOR = 'sensor';

    /** Nivel = color del punto en el panel: 1 rojo, 2 ámbar, 3 neutro. */
    public const LEVEL_CRITICAL = 1;

    public const LEVEL_WARNING = 2;

    public const LEVEL_INFO = 3;

    /** Cuántas notificaciones se muestran en el panel de la campana. */
    public const FEED_LIMIT = 30;

    protected $table            = 'notifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'industry_id', 'user_id', 'level', 'title', 'text', 'source', 'source_key', 'link', 'read_at',
    ];

    protected bool $allowEmptyInserts = false;

    /**
     * Reparte una notificación entre todos los miembros del espacio. El que
     * provocó el cambio también la recibe: es la confirmación de su acción y ya
     * la está viendo como toast.
     *
     * @param array $notification level, title, text, source, source_key, link
     */
    public function fanOut(int $industryId, array $notification): void
    {
        $members = (new IndustryUserModel())->membersForIndustry($industryId);
        if ($members === []) {
            return;
        }

        $this->sendTo(
            $industryId,
            array_map(static fn (array $member): int => (int) $member['user_id'], $members),
            $notification
        );
    }

    /**
     * Misma notificación, pero solo para los destinatarios que se piden. La
     * usan los recordatorios del calendario, que van a los encargados del
     * evento y no a todo el espacio.
     *
     * @param list<int> $userIds
     * @param array     $notification level, title, text, source, source_key, link
     */
    public function sendTo(int $industryId, array $userIds, array $notification): void
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ($userIds === []) {
            return;
        }

        $base = [
            'industry_id' => $industryId,
            'level'       => (int) ($notification['level'] ?? 3),
            'title'       => mb_substr((string) ($notification['title'] ?? 'Notificación'), 0, 160),
            'text'        => mb_substr((string) ($notification['text'] ?? ''), 0, 500),
            'source'      => (string) ($notification['source'] ?? self::SOURCE_STOCK),
            'source_key'  => mb_substr((string) ($notification['source_key'] ?? ''), 0, 120),
            'link'        => $notification['link'] ?? null,
        ];

        $batch = [];
        foreach ($userIds as $userId) {
            $batch[] = $base + ['user_id' => $userId];
        }

        $this->insertBatch($batch);
    }

    /**
     * Últimas notificaciones del usuario, con la forma que consume el panel.
     */
    public function feedFor(int $userId, int $limit = self::FEED_LIMIT): array
    {
        $rows = $this->where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->findAll($limit);

        return array_map(static fn (array $row): array => [
            'id'    => (int) $row['id'],
            'level' => (int) $row['level'],
            'title' => (string) $row['title'],
            'text'  => (string) $row['text'],
            'link'  => $row['link'],
            'date'  => self::humanDate($row['created_at']),
            'read'  => $row['read_at'] !== null,
        ], $rows);
    }

    public function unreadCountFor(int $userId): int
    {
        return $this->where('user_id', $userId)
            ->where('read_at', null)
            ->countAllResults();
    }

    public function markAllRead(int $userId): void
    {
        $this->db->table($this->table)
            ->where('user_id', $userId)
            ->where('read_at', null)
            ->update(['read_at' => date('Y-m-d H:i:s')]);
    }

    public function clearAll(int $userId): void
    {
        $this->db->table($this->table)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * "14:32" si es de hoy, "28/08" si es más vieja.
     */
    private static function humanDate(?string $createdAt): string
    {
        if ($createdAt === null || $createdAt === '') {
            return '';
        }

        $time = strtotime($createdAt);
        if ($time === false) {
            return '';
        }

        return date('Y-m-d', $time) === date('Y-m-d') ? date('H:i', $time) : date('d/m', $time);
    }
}
