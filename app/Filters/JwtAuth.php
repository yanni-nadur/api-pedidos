<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

helper('jwt');

class JwtAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');

        if (!$header || !preg_match('/Bearer\s(\S+)/', $header, $matches)) {
            return service('response')->setJSON(['message' => 'Token missing or invalid'])->setStatusCode(401);
        }

        $token = $matches[1];
        $decoded = validateJWT($token);

        if (!$decoded) {
            return service('response')->setJSON(['message' => 'Token invalid or expired'])->setStatusCode(401);
        }

        return;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
