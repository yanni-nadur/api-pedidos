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
        $totalPrice = 0;
        $items = [];

        foreach ($data['items'] as $item) {
            $product = $productModel->find($item['product_id']);
            $itemTotal = $product['price'] * $item['quantity'];
            $totalPrice += $itemTotal;

            $orderItemModel->insert([
                'order_id'   => $orderId,
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'price'      => $product['price']
            ]);

            // Order info
            $items[] = [
                'product_id' => $item['product_id'],
                'product_name' => $product['name'],
                'quantity'   => $item['quantity'],
                'price'      => $product['price'],
                'total_price' => $itemTotal
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
                'total_price' => $totalPrice,
                'items'       => $items
            ]
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
        if (!$items) {
            return $this->failNotFound('No items found for this order');
        }

        $itemDetails = [];
        foreach ($items as $item) {
            $product = $productModel->find($item['product_id']);
            if ($product) {
                $itemDetails[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $product['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => number_format($item['quantity'] * $item['price'], 2)
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
        $customerModel = new CustomerModel();
        $productModel = new ProductModel();
        $data = $this->request->getJSON(true);


        // Update request fields validation
        $order = $orderModel->find($id);
        if (!$order) {
            return $this->failNotFound('Order not found');
        }

        if (isset($data['customer_id']) && !$customerModel->find($data['customer_id'])) {
            return $this->failNotFound('Customer not found');
        }

        if (isset($data['status']) && !in_array($data['status'], ['Pending', 'Paid', 'Canceled'])) {
            return $this->failValidationErrors('Invalid status value');
        }

        // Update order status
        if (isset($data['status'])) {
            $orderModel->update($id, ['status' => $data['status']]);
        }

        if (isset($data['items']) && is_array($data['items'])) {
            $invalidProductIds = [];

            foreach ($data['items'] as $item) {
                if (!isset($item['product_id']) || !isset($item['quantity'])) {
                    return $this->failValidationErrors('Invalid item format (missing product_id or quantity)');
                }

                if (!$productModel->find($item['product_id'])) {
                    $invalidProductIds[] = $item['product_id'];
                }
            }

            if (!empty($invalidProductIds)) {
                return $this->failNotFound('The following products were not found: ' . implode(', ', $invalidProductIds));
            }

            foreach ($data['items'] as $item) {
                if (!$productModel->find($item['product_id'])) {
                    continue;
                }
    
                // Check if the item already exists in the order
                $existingItem = $orderItemModel->where('order_id', $id)
                    ->where('product_id', $item['product_id'])
                    ->first();
    
                if ($existingItem) {
                    $orderItemModel->update($existingItem['id'], [
                        'quantity' => $item['quantity'],
                        'price' => $item['price'] ?? $existingItem['price'] // Old price if not specified
                    ]);
                } else {
                    $orderItemModel->insert([
                        'order_id' => $id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'] ?? 0 // Default price is 0
                    ]);
                }
            }
        }

        // Updated order details
        $updatedOrder = $orderModel->find($id);
        $updatedItems = $orderItemModel->where('order_id', $id)->findAll();

        return $this->respond([
            'header' => [
                'status' => 200,
                'message' => 'Order updated successfully'
            ],
            'data' => [
                'order' => $updatedOrder,
                'items' => $updatedItems
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
