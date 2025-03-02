<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Jwt extends BaseConfig
{
    public string $secretKey;
    public int $tokenExpiration;

    public function __construct()
    {
        $this->secretKey = env('JWT_SECRET_KEY', 'sua-chave-padrao-aqui');
        $this->tokenExpiration = env('JWT_EXPIRATION', 3600);
    }
}