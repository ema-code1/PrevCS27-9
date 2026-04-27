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
    protected $allowedFields    = ['nombre', 'apellido', 'email', 'password_hash', 'foto_perfil'];
    protected $beforeInsert     = ['hashPassword'];
    protected $beforeUpdate     = ['hashPassword'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    protected function hashPassword(array $data): array
    {
        if (! isset($data['data']['password_hash']) || $data['data']['password_hash'] === '') {
            return $data;
        }

        $password = (string) $data['data']['password_hash'];

        if (password_get_info($password)['algoName'] !== 'unknown') {
            return $data;
        }

        $data['data']['password_hash'] = password_hash($password, PASSWORD_DEFAULT);

        return $data;
    }
}
