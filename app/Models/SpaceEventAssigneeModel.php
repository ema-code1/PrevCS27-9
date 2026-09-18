<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Encargados de un evento del calendario (selector "Encargado(s)").
 *
 * Solo guarda el vínculo evento ↔ usuario: el payload manda los ids y el JS
 * les pone cara con la lista de miembros del espacio, que ya viaja una sola
 * vez. Quién puede ser encargado lo decide el controlador, que recorta la
 * lista contra los miembros; un id de otro espacio nunca llega acá.
 */
class SpaceEventAssigneeModel extends Model
{
    protected $table            = 'space_event_assignees';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['event_id', 'user_id'];

    protected bool $allowEmptyInserts = false;

    public function userIdsFor(int $eventId): array
    {
        return array_map(
            'intval',
            array_column($this->select('user_id')->where('event_id', $eventId)->findAll(), 'user_id')
        );
    }

    /**
     * Deja exactamente los encargados que pide la lista: inserta los que
     * faltan y borra los que sobran. Se llama con la lista ya filtrada contra
     * los miembros del espacio.
     */
    public function sync(int $eventId, array $userIds): void
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        $current = $this->userIdsFor($eventId);

        $remove = array_diff($current, $userIds);
        if ($remove !== []) {
            $this->where('event_id', $eventId)
                ->whereIn('user_id', array_values($remove))
                ->delete();
        }

        $add = array_diff($userIds, $current);
        if ($add === []) {
            return;
        }

        $this->insertBatch(array_map(
            static fn (int $userId): array => ['event_id' => $eventId, 'user_id' => $userId],
            array_values($add)
        ));
    }

    /**
     * Encargados de todos los eventos de un espacio en una sola consulta
     * (evita el N+1 al armar el payload del calendario).
     *
     * @return array<int, list<int>>
     */
    public function idsByEvent(int $industryId): array
    {
        $rows = $this->db->table('space_event_assignees a')
            ->select('a.event_id, a.user_id')
            ->join('space_events e', 'e.id = a.event_id')
            ->join('users u', 'u.id = a.user_id')
            ->where('e.industry_id', $industryId)
            ->orderBy('u.nombre', 'ASC')
            ->orderBy('u.apellido', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row['event_id']][] = (int) $row['user_id'];
        }

        return $map;
    }

    /**
     * Miembros del espacio con la forma que consume el selector del JS:
     * nombre, iniciales para el avatar, foto si la hay y el rol como renglón
     * secundario del menú. Se arma sobre membersForIndustry() para no repetir
     * el join con users.
     */
    public static function optionsFromMembers(array $members): array
    {
        return array_map(static function (array $member): array {
            $name = trim(trim((string) ($member['nombre'] ?? '')) . ' ' . trim((string) ($member['apellido'] ?? '')));
            if ($name === '') {
                $name = 'Usuario #' . (int) $member['user_id'];
            }

            $initials = '';
            foreach (array_slice(preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 2) as $part) {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            }

            $photo = trim((string) ($member['foto_perfil'] ?? ''));

            return [
                'id'       => (int) $member['user_id'],
                'name'     => $name,
                'initials' => $initials !== '' ? $initials : '?',
                'photo'    => $photo !== '' ? base_url('uploads/avatars/' . rawurlencode($photo)) : null,
                'role'     => ($member['role'] ?? '') === IndustryUserModel::ROLE_ADMIN ? 'Admin principal' : 'Supervisor',
            ];
        }, array_values($members));
    }
}
