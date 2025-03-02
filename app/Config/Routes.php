<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->setDefaultNamespace('App\Controllers'); // Just to be sure the namespace is correct

$routes->get('/', 'Home::index');
$routes->post('auth/login', 'AuthController::login');

// API Routes (OBS: Had to adjust since I want to use the resource function)

$routes->group('', ['filter' => 'jwtAuth'], function ($routes) {
    $routes->resource('customers', ['controller' => 'CustomerController']);
    $routes->resource('products', ['controller' => 'ProductController']);
    $routes->resource('orders', ['controller' => 'OrderController']);
});

// $routes->resource('customers', [
//     'controller' => 'CustomerController',
//     'filter' => 'jwtAuth'
// ]);

// $routes->resource('products', [
//     'controller' => 'ProductController',
//     'filter' => 'jwtAuth'
// ]);

// $routes->resource('orders', [
//     'controller' => 'OrderController',
//     'filter' => 'jwtAuth'
// ]);