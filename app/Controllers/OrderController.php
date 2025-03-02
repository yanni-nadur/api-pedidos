<?php

namespace App\Controllers;

use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\ProductModel;
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

        if (!isset($data['customer_id']) || !isset($data['items']) || !is_array($data['items'])) {
            return $this->failValidationErrors('Invalid data');
        }

        $orderData = ['customer_id' => $data['customer_id'], 'status' => 'Pending'];
        $orderModel->insert($orderData);
        $orderId = $orderModel->getInsertID();

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
            return $this->failNotFound('Order not found');
        }

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

        $order = $orderModel->find($id);
        if (!$order) {
            return $this->failNotFound('Order not found');
        }

        if (isset($data['status']) && !in_array($data['status'], ['Pending', 'Paid', 'Canceled'])) {
            return $this->failValidationErrors('Invalid status value');
        }

        if (isset($data['status'])) {
            $orderModel->update($id, ['status' => $data['status']]);
        }

        if (isset($data['items']) && is_array($data['items'])) {
            $existingItems = $orderItemModel->where('order_id', $id)->findAll();
            $existingItemIds = array_column($existingItems, 'product_id');

            foreach ($data['items'] as $item) {
                if (!isset($item['product_id'], $item['quantity'], $item['price'])) {
                    return $this->failValidationErrors('Invalid item format');
                }

                if (!$productModel->find($item['product_id'])) {
                    return $this->failNotFound('Product not found');
                }

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

        return $this->respond([
            'header' => [
                'status' => 200,
                'message' => 'Order updated successfully'
            ],
            'data' => $orderModel->find($id)
        ]);
    }

    public function delete($id = null)
    {
        $orderModel = new OrderModel();
        $orderItemModel = new OrderItemModel();

        if (!$orderModel->find($id)) {
            return $this->failNotFound('Order not found');
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
