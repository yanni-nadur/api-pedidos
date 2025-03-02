<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Config\Jwt as JwtConfig;

if (!function_exists('generateJWT')) {
    function generateJWT($userId)
    {
        $config = new JwtConfig();

        $payload = [
            'iss' => 'localhost',
            'aud' => 'localhost',
            'iat' => time(),
            'exp' => time() + $config->tokenExpiration,
            'sub' => $userId,
        ];

        return JWT::encode($payload, $config->secretKey, 'HS256');
    }
}

if (!function_exists('validateJWT')) {
    function validateJWT($token)
    {
        try {
            $config = new JwtConfig();
            return JWT::decode($token, new Key($config->secretKey, 'HS256'));
        } catch (Exception $e) {
            return null;
        }
    }
}
