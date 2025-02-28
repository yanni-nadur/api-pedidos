<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table = 'customers';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'cpf_cnpj', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
}
