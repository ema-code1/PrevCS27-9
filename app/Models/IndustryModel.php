<?php

namespace App\Models;

use CodeIgniter\Model;

class IndustryModel extends Model
{
    protected $table            = 'industries';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['industry_uid', 'company_name', 'space_name', 'sector', 'space_password_hash', 'created_by'];

    // Mismo patrón anti doble-hash que UserModel para la contraseña del espacio
    protected $beforeInsert = ['hashSpacePassword'];
    protected $beforeUpdate = ['hashSpacePassword'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected function hashSpacePassword(array $data): array
    {
        if (! isset($data['data']['space_password_hash']) || $data['data']['space_password_hash'] === '') {
            return $data;
        }

        $password = (string) $data['data']['space_password_hash'];

        // Si ya viene hasheada, no la vuelve a hashear
        if (password_get_info($password)['algoName'] !== 'unknown') {
            return $data;
        }

        $data['data']['space_password_hash'] = password_hash($password, PASSWORD_DEFAULT);

        return $data;
    }

    /**
     * Busca una industria por su UID publico.
     */
    public function findByUid(string $uid): ?array
    {
        return $this->where('industry_uid', $uid)->first();
    }

    /**
     * Genera un ID unico e irrepetible derivado de la empresa/sector,
     * por ejemplo: SJ-IND-8F42A1
     */
    public function generateUniqueUid(string $company, string $sector = ''): string
    {
        $prefix = $this->buildPrefix($company, $sector);

        // Reintenta hasta encontrar un sufijo aleatorio que no exista
        do {
            $suffix = strtoupper(bin2hex(random_bytes(3))); // 6 chars hex
            $uid    = $prefix . '-IND-' . $suffix;
        } while ($this->where('industry_uid', $uid)->countAllResults() > 0);

        return $uid;
    }

    /**
     * Iniciales (hasta 2-3) del nombre de empresa; si no sirve, del sector;
     * fallback a "PCS".
     */
    private function buildPrefix(string $company, string $sector): string
    {
        foreach ([$company, $sector] as $source) {
            $words = preg_split('/\s+/', trim((string) $source), -1, PREG_SPLIT_NO_EMPTY);

            if ($words === [] || $words === false) {
                continue;
            }

            $initials = '';
            foreach ($words as $word) {
                $initials .= strtoupper((string) mb_substr($word, 0, 1));
                if (mb_strlen($initials) >= 3) {
                    break;
                }
            }

            // Solo letras/numeros ASCII para mantener el UID limpio
            $initials = preg_replace('/[^A-Z0-9]/', '', $initials);

            if ($initials !== '') {
                return mb_strlen($initials) === 1 ? $initials . 'X' : $initials;
            }
        }

        return 'PCS';
    }
}
