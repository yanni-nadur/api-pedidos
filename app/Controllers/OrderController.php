<?php

namespace App\Controllers;

use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\CustomerModel;
use App\Models\ProductModel;
use CodeIgniter\RESTful\ResourceController;

class OrderController extends ResourceController
{
    protected $modelName = 'App\Models\OrderModel';
    protected $format    = 'json';

    public function index()
    {
        $perPage = $this->request->getGet('per_page') ?? 3; 
        $page = $this->request->getGet('page') ?? 1; 

        $query = $this->model;

        foreach (['customer_id', 'status', 'created_at', 'updated_at'] as $field) {
            if ($value = $this->request->getGet($field)) {
                $query = $query->like($field, $value);
            }
        }

        // Clone the query to count the total number of records without modifying the original query.
        $totalRecords = (clone $query)->countAllResults(false);
        $totalPages = ceil($totalRecords / $perPage);

        if ($totalRecords === 0 || $page > $totalPages) {
            return $this->respond([
                'header' => ['status' => 200, 'message' => 'No orders found'],
                'pagination' => ['current_page' => $page, 'per_page' => $perPage, 'total_orders' => $totalRecords],
                'data' => []
            ]);
        }

        return $this->respond([
            'header' => ['status' => 200, 'message' => 'Orders list retrieved successfully'],
            'pagination' => ['current_page' => $page, 'per_page' => $perPage, 'total_orders' => $totalRecords],
            'data' => $query->paginate($perPage, 'default', $page)
        ]);
    }

    public function create()
    {
        $orderModel = new OrderModel();
        $orderItemModel = new OrderItemModel();
        $customerModel = new CustomerModel();
        $productModel = new ProductModel();
        $data = $this->request->getJSON(true);

        if (!isset($data['customer_id']) || !isset($data['items']) || !is_array($data['items'])) {
            return $this->failValidationErrors('Invalid data');
        }

        // Validation to check if customer exists based on the ID
        $customer = $customerModel->find($data['customer_id']);
        if (!$customer) {
            return $this->failNotFound('Customer not found');
        }

        // Same as above, but checking order products ID's
        $invalidProductIds = [];
        foreach ($data['items'] as $item) {
            if (!isset($item['product_id']) || !isset($item['quantity'])) {
                return $this->failValidationErrors('Missing product_id or quantity');
            }
    
            $product = $productModel->find($item['product_id']);
            if (!$product) {
                $invalidProductIds[] = $item['product_id'];
            }
        }

        if (!empty($invalidProductIds)) {
            return $this->failNotFound('The following products were not found: ' . implode(', ', $invalidProductIds));
        }

        // Creating order in DB
        $orderData = ['customer_id' => $data['customer_id'], 'status' => 'Pending'];
        $orderModel->insert($orderData);
        $orderId = $orderModel->getInsertID();

        // Aux variables for order info
        $orderPrice = 0;
        $items = [];

        foreach ($data['items'] as $item) {
            $product = $productModel->find($item['product_id']);
            $itemTotal = number_format($product['price'] * $item['quantity'], 2);
            $orderPrice += $itemTotal;

            $orderItemModel->insert([
                'order_id'   => $orderId,
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'price'      => $product['price']
            ]);

            // Order info
            $items[] = [
                'product_id'        => $item['product_id'],
                'product_name'      => $product['name'],
                'quantity'          => $item['quantity'],
                'product_price'     => $product['price'],
                'total_price'       => $itemTotal
            ];
        }

        return $this->respondCreated([
            'header' => [
                'status' => 201,
                'message' => 'Order created successfully'
            ],
            'data' => [
                'order_id'    => $orderId,
                'customer_id' => $data['customer_id'],
                'status'      => 'Pending',
                'order_price' => $orderPrice,
                'items'       => $items
            ]
        ]);
    }

    public function show($id = null)
    {
        $orderModel = new OrderModel();
        $orderItemModel = new OrderItemModel();
        $productModel = new ProductModel();

        // Find the order
        $order = $orderModel->find($id);
        if (!$order) {
            return $this->failNotFound('Order not found');
        }

        // Retrieve order items
        $items = $orderItemModel->where('order_id', $id)->findAll();
        
        if (!$items) {
            return $this->failNotFound('No items found for this order');
        }

        $itemDetails = [];
        $totalPrice = 0; 

        foreach ($items as $item) {
            $product = $productModel->find($item['product_id']);

            if ($product) {
                $itemTotalPrice = $item['quantity'] * $item['price'];
                $totalPrice += $itemTotalPrice;

                $itemDetails[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $product['name'],
                    'quantity' => $item['quantity'],
                    'product_price' => $item['price'],
                    'total_price' => number_format($itemTotalPrice, 2)
                ];
            }
        }

        return $this->respond([
            'header' => [
                'status'  => 200,
                'message' => 'Order retrieved successfully'
            ],
            'data' => [
                'order' => [
                    'order_id' => $order['id'],
                    'status' => $order['status'],
                    'total_price' => number_format($totalPrice, 2),
                    'created_at' => $order['created_at'],
                    'updated_at' => $order['updated_at']
                ],
                'items' => $itemDetails
            ]
        ]);
    }


    public function update($id = null)
    {
        $orderModel = new OrderModel();
        $orderItemModel = new OrderItemModel();
        $data = $this->request->getJSON(true);
    
        $order = $orderModel->find($id);
        if (!$order) {
            return $this->failNotFound('Order not found');
        }
    
        if (!isset($data['status']) || !in_array($data['status'], ['Pending', 'Paid', 'Canceled'])) {
            return $this->failValidationErrors('Invalid or missing status value');
        }
    
        if ($order['status'] !== $data['status']) {
            $orderModel->update($id, ['status' => $data['status']]);
        }
    
        // Find updated order
        $updatedOrder = $orderModel->find($id);
        $orderItems = $orderItemModel->where('order_id', $id)->findAll();
    
        $totalPrice = 0;
        foreach ($orderItems as &$item) {
            $item['total_price'] = $item['quantity'] * $item['price'];
            $totalPrice += $item['total_price'];
        }
    
        return $this->respond([
            'header' => [
                'status' => 200,
                'message' => 'Order status updated successfully'
            ],
            'data' => [
                'order' => [
                    'order_id' => $updatedOrder['id'],
                    'status' => $updatedOrder['status'],
                    'order_price' => $totalPrice,
                    'created_at' => $updatedOrder['created_at'],
                    'updated_at' => $updatedOrder['updated_at']
                ],
                'items' => $orderItems
            ]
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
