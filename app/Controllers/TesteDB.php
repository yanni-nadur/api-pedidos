<?php 

namespace App\Controllers;
use CodeIgniter\Controller;
use Config\Database;

class TesteDB extends Controller
{
    public function index()
    {
        $db = Database::connect();
        
        if ($db->connect_error) {
            return $this->response->setJSON(['status' => 'Erro', 'mensagem' => $db->connect_error]);
        }

        return $this->response->setJSON(['status' => 'Sucesso', 'mensagem' => 'Conexão com MySQL bem-sucedida!']);
    }
}