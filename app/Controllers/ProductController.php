<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ProductModel;

class ProductController extends ResourceController
{
    protected $modelName = 'App\Models\ProductModel';
    protected $format    = 'json';

    public function index()
    {
        $perPage = $this->request->getGet('per_page') ?? 3; 
        $page = $this->request->getGet('page') ?? 1; 

        $query = $this->model;

        foreach (['name', 'price', 'created_at', 'updated_at'] as $field) {
            if ($value = $this->request->getGet($field)) {
                $query = $query->like($field, $value);
            }
        }

        // Clone the query to count the total number of records without modifying the original query
        $totalRecords = (clone $query)->countAllResults(false);
        $totalPages = ceil($totalRecords / $perPage);

        if ($totalRecords === 0 || $page > $totalPages) {
            return $this->respond([
                'header' => ['status' => 200, 'message' => 'No products found'],
                'pagination' => ['current_page' => $page, 'per_page' => $perPage, 'total_products' => $totalRecords],
                'data' => []
            ]);
        }

        return $this->respond([
            'header' => ['status' => 200, 'message' => 'Products list retrieved successfully'],
            'pagination' => ['current_page' => $page, 'per_page' => $perPage, 'total_products' => $totalRecords],
            'data' => $query->paginate($perPage, 'default', $page)
        ]);
    }


    public function create()
    {
        $data = $this->request->getJSON(true);

        // Simple conversion to allow both "20.00" and "20,00" hehe
        if (isset($data['price'])) {
            $data['price'] = str_replace(',', '.', $data['price']);
        }

        if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0) {
            return $this->failValidationErrors('The price must be higher than 0');
        }

        if (!$this->model->insert($data)) {
            return $this->failValidationErrors('Validation error');
        }

        return $this->respondCreated([
            'header' => [
                'status'  => 201,
                'message' => 'Product created successfully'
            ],
            'data' => $data
        ]);
    }

    public function show($id = null)
    {
        $product = $this->model->find($id);

        if (!$product) {
            return $this->failNotFound('Product not found');
        }

        return $this->respond([
            'header' => [
                'status'  => 200,
                'message' => 'Product found'
            ],
            'data' => $product
        ]);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        $product = $this->model->find($id);
        
        if (!$product) {
            return $this->failNotFound('Product not found');
        }

        if (empty($data)) {
            return $this->failValidationErrors('No data provided for update');
        }

        // Same conversion of my create method
        if (isset($data['price'])) {
            $data['price'] = str_replace(',', '.', $data['price']);

            if (!is_numeric($data['price']) || $data['price'] <= 0) {
                return $this->failValidationErrors('The price must be a positive number');
            }
        }

        if (!$this->model->update($id, $data)) {
            return $this->failValidationErrors('Validation error');
        }

        return $this->respond([
            'header' => [
                'status'  => 200,
                'message' => 'Product updated successfully'
            ],
            'data' => $this->model->find($id)
        ]);
    }

    public function delete($id = null)
    {
        if (!$this->model->find($id)) {
            return $this->failNotFound('Product not found');
        }

        $this->model->delete($id);

        return $this->respondDeleted([
            'header' => [
                'status'  => 200,
                'message' => 'Product deleted successfully'
            ],
            'data' => null
        ]);
    }
}
