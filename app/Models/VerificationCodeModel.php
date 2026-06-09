<?php

namespace App\Models;

use CodeIgniter\Model;

class VerificationCodeModel extends Model
{
    protected $table            = 'verification_codes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'purpose',
        'code_hash',
        'attempts',
        'expires_at',
        'used_at',
        'created_at',
    ];
}
