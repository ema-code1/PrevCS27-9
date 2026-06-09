<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nombre',
        'apellido',
        'email',
        'password_hash',
        'foto_perfil',
        'email_verified_at',
        'two_factor_enabled',
    ];
    protected $beforeInsert     = ['hashPassword'];
    protected $beforeUpdate     = ['hashPassword'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];


// HASHEO de contraseñas

    protected function hashPassword(array $data): array
    {
        // Si no existe $data['data']['password_hash'] o está vacío, no hace nada y devuelve $data.
        if (! isset($data['data']['password_hash']) || $data['data']['password_hash'] === '') {
            return $data;
        }
        // Convierte ese valor a string y ...
        $password = (string) $data['data']['password_hash'];

        // ... comprueba con password_get_info si ya es un hash (evita doble-hash).
        if (password_get_info($password)['algoName'] !== 'unknown') {
            return $data;
        }
        // Si no es un hash, lo hashea.
        $data['data']['password_hash'] = password_hash($password, PASSWORD_DEFAULT);

        return $data;
    }
}
