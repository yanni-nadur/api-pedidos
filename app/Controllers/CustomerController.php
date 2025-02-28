<?php 

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CustomerModel;

class CustomerController extends ResourceController
{
    protected $modelName = 'App\Models\CustomerModel';
    protected $format    = 'json';

    public function index()
    {
        $customers = $this->model->findAll();

        return $this->respond([
            'header' => [
                'status'  => 200,
                'message' => 'Data retrieved successfully'
            ],
            'data' => $customers
        ]);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        if ($this->model->insert($data)) {
            return $this->respondCreated([
                'header' => [
                    'status' => 201,
                    'message' => 'Customer created successfully'
                ],
                'data' => $data
            ]);
        }

        return $this->failValidationErrors([
            'header' => [
                'status' => 400,
                'message' => 'Validation error'
            ],
            'data' => $this->model->errors()
        ]);
    }

    public function show($id = null)
    {
        $customer = $this->model->find($id);

        if ($customer) {
            return $this->respond([
                'header' => [
                    'status'  => 200,
                    'message' => 'Customer found'
                ],
                'data' => $customer
            ]);
        }

        return $this->failNotFound([
            'header' => [
                'status'  => 404,
                'message' => 'Customer not found'
            ],
            'data' => null
        ]);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);

        if (!$this->model->find($id)) {
            return $this->failNotFound([
                'header' => [
                    'status'  => 404,
                    'message' => 'Customer not found'
                ],
                'data' => null
            ]);
        }

        if ($this->model->update($id, $data)) {
            return $this->respond([
                'header' => [
                    'status' => 200,
                    'message' => 'Customer updated successfully'
                ],
                'data' => $this->model->find($id) // Retorna os dados atualizados
            ]);
        }
        
        return $this->failValidationErrors([
            'header' => [
                'status' => 400,
                'message' => 'Validation error'
            ],
            'data' => $this->model->errors()
        ]);
    }

    public function delete($id = null)
    {
        if (!$this->model->find($id)) {
            return $this->failNotFound([
                'header' => [
                    'status'  => 404,
                    'message' => 'Customer not found'
                ],
                'data' => null
            ]);
        }

        if ($this->model->delete($id)) {
            return $this->respondDeleted([
                'header' => [
                    'status' => 200,
                    'message' => 'Customer deleted successfully'
                ],
                'data' => null
            ]);
        }

        return $this->failServerError([
            'header' => [
                'status'  => 500,
                'message' => 'Error deleting customer'
            ],
            'data' => null
        ]);
    }
}
