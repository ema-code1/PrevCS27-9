<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Etiquetas propias del calendario de un espacio.
 *
 * Se suman a SpaceEventModel::TAGS, que son fijas y las tienen todos los
 * espacios. Los eventos siguen guardando los nombres (space_events.tags es
 * JSON), así que una etiqueta borrada deja de ofrecerse pero no rompe los
 * eventos que ya la tenían: el controlador la limpia al borrarla.
 */
class SpaceEventTagModel extends Model
{
    /** Tope por espacio: el selector del diálogo se vuelve inmanejable. */
    public const MAX_PER_INDUSTRY = 40;

    public const MAX_LENGTH = 40;

    protected $table            = 'space_event_tags';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['industry_id', 'name', 'created_by'];

    protected bool $allowEmptyInserts = false;

    public function forIndustry(int $industryId): array
    {
        return $this->where('industry_id', $industryId)
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    /** Solo los nombres, para validar lo que manda el cliente. */
    public function namesForIndustry(int $industryId): array
    {
        return array_values(array_column($this->forIndustry($industryId), 'name'));
    }

    /** Etiquetas propias con la forma que consume el JS. */
    public function payloadForIndustry(int $industryId): array
    {
        return array_map(static fn (array $row): array => [
            'id'   => (int) $row['id'],
            'name' => (string) $row['name'],
        ], $this->forIndustry($industryId));
    }

    public function findInIndustry(int $tagId, int $industryId): ?array
    {
        return $this->where('id', $tagId)
            ->where('industry_id', $industryId)
            ->first();
    }

    /** Busca por nombre exacto (para no crear duplicados). */
    public function findNamed(int $industryId, string $name): ?array
    {
        return $this->where('industry_id', $industryId)
            ->where('name', $name)
            ->first();
    }

    public function countForIndustry(int $industryId): int
    {
        return $this->where('industry_id', $industryId)->countAllResults();
    }
}
