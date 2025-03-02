<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class AuthController extends ResourceController
{
    public function login()
    {
        helper('jwt');

        $userId = 123; 
        $token = generateJWT($userId);

        return $this->respond(['token' => $token]);
    }
}
