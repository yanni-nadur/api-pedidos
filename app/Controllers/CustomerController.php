<?php 

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class CustomerController extends ResourceController
{
    protected $modelName = 'App\Models\CustomerModel';
    protected $format    = 'json';

    public function index()
    {
        $perPage = $this->request->getGet('per_page') ?? 3; 
        $page = $this->request->getGet('page') ?? 1; 

        $query = $this->model;

        foreach (['name', 'cpf', 'created_at', 'updated_at'] as $field) {
            if ($value = $this->request->getGet($field)) {
                $query = $query->like($field, $value);
            }
        }

        $totalRecords = (clone $query)->countAllResults(false);
        $totalPages = ceil($totalRecords / $perPage);

        if ($totalRecords === 0 || $page > $totalPages) {
            return $this->respond([
                'header' => ['status' => 200, 'message' => 'No customers found'],
                'pagination' => ['current_page' => $page, 'per_page' => $perPage, 'total_customers' => $totalRecords],
                'data' => []
            ]);
        }

        return $this->respond([
            'header' => ['status' => 200, 'message' => 'Customers list retrieved successfully'],
            'pagination' => ['current_page' => $page, 'per_page' => $perPage, 'total_customers' => $totalRecords],
            'data' => $query->paginate($perPage, 'default', $page)
        ]);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        if (!$data) {
            return $this->failValidationErrors('No data provided');
        }
    
        if (isset($data['cpf']) && !preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $data['cpf'])) {
            return $this->failValidationErrors('Invalid CPF format. The correct format is XXX.XXX.XXX-XX');
        }
    
        if ($this->model->where('cpf', $data['cpf'])->first()) {
            return $this->failValidationErrors('CPF already exists in the system');
        }
        
        if (!$this->model->insert($data)) {
            return $this->failValidationErrors('Validation error');
        }

        return $this->respondCreated([
            'header' => [
                'status'  => 201,
                'message' => 'Customer created successfully'
            ],
            'data' => $data
        ]);
    }

    public function show($id = null)
    {
        $customer = $this->model->find($id);

        if (!$customer) {
            return $this->failNotFound('Customer not found');
        }

        return $this->respond([
            'header' => [
                'status'  => 200,
                'message' => 'Customer found'
            ],
            'data' => $customer
        ]);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);

        if (!$this->model->find($id)) {
            return $this->failNotFound('Customer not found');
        }

        if (empty($data)) {
            return $this->failValidationErrors('No data provided for update');
        }

        if (isset($data['cpf']) && !preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $data['cpf'])) {
            return $this->failValidationErrors('Invalid CPF format. The correct format is XXX.XXX.XXX-XX');
        }

        if (!$this->model->update($id, $data)) {
            return $this->failValidationErrors('Validation error');
        }

        return $this->respond([
            'header' => [
                'status'  => 200,
                'message' => 'Customer updated successfully'
            ],
            'data' => $this->model->find($id)
        ]);
    }

    public function delete($id = null)
    {
        $customer = $this->model->find($id);
    
        if (!$customer) {
            return $this->failNotFound('Customer not found');
        }
    
        if ($this->model->delete($id)) {
            return $this->respondDeleted([
                'header' => [
                    'status'  => 200,
                    'message' => 'Customer ' . $customer['name'] . ' deleted successfully'
                ],
                'data' => null
            ]);
        }
    
        return $this->failServerError('Error deleting customer');
    }
}
