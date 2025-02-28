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
        $products = $this->model->findAll();

        return $this->respond([
            'header' => [
                'status'  => 200,
                'message' => 'Data retrieved successfully'
            ],
            'data' => $products
        ]);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        if (isset($data['price'])) {
            // Simple conversion to allow both "20.00" and "20,00" hehe
            $data['price'] = str_replace(',', '.', $data['price']);
        }

        // Product price validation
        if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0) {
            return $this->failValidationErrors([
                'header' => [
                    'status'  => 400,
                    'message' => 'Validation error'
                ],
                'data' => ['price' => 'The price must be higher than 0']
            ]);
        }

        if ($this->model->insert($data)) {
            return $this->respondCreated([
                'header' => [
                    'status'  => 201,
                    'message' => 'Product created successfully'
                ],
                'data' => $data
            ]);
        }

        return $this->failValidationErrors([
            'header' => [
                'status'  => 400,
                'message' => 'Validation error'
            ],
            'data' => $this->model->errors()
        ]);
    }


    public function show($id = null)
    {
        $product = $this->model->find($id);

        if ($product) {
            return $this->respond([
                'header' => [
                    'status'  => 200,
                    'message' => 'Product found'
                ],
                'data' => $product
            ]);
        }

        return $this->failNotFound([
            'header' => [
                'status'  => 404,
                'message' => 'Product not found'
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
                    'message' => 'Product not found'
                ],
                'data' => null
            ]);
        }

        if ($this->model->update($id, $data)) {
            return $this->respond([
                'header' => [
                    'status'  => 200,
                    'message' => 'Product updated successfully'
                ],
                'data' => $this->model->find($id)
            ]);
        }

        return $this->failValidationErrors([
            'header' => [
                'status'  => 400,
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
                    'message' => 'Product not found'
                ],
                'data' => null
            ]);
        }

        if ($this->model->delete($id)) {
            return $this->respondDeleted([
                'header' => [
                    'status'  => 200,
                    'message' => 'Product deleted successfully'
                ],
                'data' => null
            ]);
        }

        return $this->failServerError([
            'header' => [
                'status'  => 500,
                'message' => 'Error deleting product'
            ],
            'data' => null
        ]);
    }
}
