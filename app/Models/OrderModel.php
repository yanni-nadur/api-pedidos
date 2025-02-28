<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';
    protected $allowedFields = ['customer_id', 'status', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
}