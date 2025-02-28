<?php

namespace App\Controllers;

use App\Models\OrderModel;
use App\Models\OrderItemModel;
use CodeIgniter\RESTful\ResourceController;

class OrderController extends ResourceController
{
    protected $modelName = 'App\Models\OrderModel';
    protected $format    = 'json';

    public function index()
    {
        $orders = $this->model->findAll();

        return $this->respond([
            'header' => [
                'status'  => 200,
                'message' => 'Orders retrieved successfully'
            ],
            'data' => $orders
        ]);
    }

    public function create()
    {
        $orderModel = new OrderModel();
        $orderItemModel = new OrderItemModel();
        $data = $this->request->getJSON(true);

        // Checking requisition data, simple validation
        if (!isset($data['customer_id']) || !isset($data['items']) || !is_array($data['items'])) {
            return $this->failValidationErrors([
                'header' => [
                    'status' => 400,
                    'message' => 'Invalid data'
                ],
                'data' => null
            ]);
        }

        $orderData = ['customer_id' => $data['customer_id'], 'status' => 'Pending'];
        $orderModel->insert($orderData);
        $orderId = $orderModel->getInsertID();

        // Iteration for order items
        foreach ($data['items'] as $item) {
            $orderItemModel->insert([
                'order_id'   => $orderId,
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'price'      => $item['price']
            ]);
        }

        return $this->respondCreated([
            'header' => [
                'status' => 201,
                'message' => 'Order created successfully'
            ],
            'data' => ['order_id' => $orderId]
        ]);
    }

    public function show($id = null)
    {
        $orderModel = new OrderModel();
        $orderItemModel = new OrderItemModel();

        $order = $orderModel->find($id);
        if (!$order) {
            return $this->failNotFound([
                'header' => [
                    'status' => 404,
                    'message' => 'Order not found'
                ],
                'data' => null
            ]);
        }

        // Find the order items on the temporary table
        $items = $orderItemModel->where('order_id', $id)->findAll();

        return $this->respond([
            'header' => [
                'status'  => 200,
                'message' => 'Order retrieved successfully'
            ],
            'data' => [
                'order' => $order,
                'items' => $items
            ]
        ]);
    }

    public function update($id = null)
    {
        $orderModel = new OrderModel();
        $orderItemModel = new OrderItemModel();
        $productModel = new ProductModel();
        
        $data = $this->request->getJSON(true);

        // Verifying if the product exists
        $order = $orderModel->find($id);
        if (!$order) {
            return $this->failNotFound([
                'header' => [
                    'status' => 404,
                    'message' => 'Order not found'
                ],
                'data' => null
            ]);
        }

        // Checking if the requisition status actually it's valid
        if (isset($data['status'])) {
            if (!in_array($data['status'], ['Pending', 'Paid', 'Canceled'])) {
                return $this->failValidationErrors([
                    'header' => [
                        'status' => 400,
                        'message' => 'Invalid status value'
                    ],
                    'data' => null
                ]);
            }
            $orderModel->update($id, ['status' => $data['status']]);
        }

        // Updating order items
        if (isset($data['items']) && is_array($data['items'])) {
            $existingItems = $orderItemModel->where('order_id', $id)->findAll();
            $existingItemIds = array_column($existingItems, 'product_id');

            foreach ($data['items'] as $item) {

                // Checking for must-have fields 
                if (!isset($item['product_id'], $item['quantity'], $item['price'])) {
                    return $this->failValidationErrors([
                        'header' => [
                            'status' => 400,
                            'message' => 'Invalid item format'
                        ],
                        'data' => null
                    ]);
                }

                // Verify if the product exists
                if (!$productModel->find($item['product_id'])) {
                    return $this->failNotFound([
                        'header' => [
                            'status' => 404,
                            'message' => 'Product not found'
                        ],
                        'data' => null
                    ]);
                }

                // Update the existing item or insert if not there before
                if (in_array($item['product_id'], $existingItemIds)) {
                    $orderItemModel->where('order_id', $id)
                        ->where('product_id', $item['product_id'])
                        ->set([
                            'quantity' => $item['quantity'],
                            'price' => $item['price']
                        ])->update();
                } else {
                    $orderItemModel->insert([
                        'order_id'   => $id,
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'],
                        'price'      => $item['price']
                    ]);
                }
            }
        }

        // Return updated order
        $updatedOrder = $orderModel->find($id);
        return $this->respond([
            'header' => [
                'status' => 200,
                'message' => 'Order updated successfully'
            ],
            'data' => $updatedOrder
        ]);
    }


    public function delete($id = null)
    {
        $orderModel = new OrderModel();
        $orderItemModel = new OrderItemModel();

        $order = $orderModel->find($id);
        if (!$order) {
            return $this->failNotFound([
                'header' => [
                    'status' => 404,
                    'message' => 'Order not found'
                ],
                'data' => null
            ]);
        }

        $orderItemModel->where('order_id', $id)->delete();
        $orderModel->delete($id);

        return $this->respondDeleted([
            'header' => [
                'status' => 200,
                'message' => 'Order deleted successfully'
            ],
            'data' => ['order_id' => $id]
        ]);
    }
}
