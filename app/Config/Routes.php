<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->setDefaultNamespace('App\Controllers'); // Just to be sure the namespace is correct

$routes->get('/', 'Home::index');

// API Routes (OBS: Had to adjust since I want to use the resource function)
$routes->resource('customers', ['controller' => 'CustomerController']);
$routes->resource('products', ['controller' => 'ProductController']);
$routes->resource('orders', ['controller' => 'OrderController']);
