# Orders API - Codeigniter 4

## About the Project
This project is a REST API developed with **Codeigniter 4** and **MySQL** for managing purchase orders. It includes CRUD operations for **customers**, **products**, and **orders**, as well as authentication via **JWT**.

## Technologies Used
- **PHP** with **Codeigniter 4**
- **MySQL** for the database
- **JWT** for authentication
- **Migrations** for database management

## Installation and Execution
### 1️⃣ **Clone the repository**
```bash
git clone https://github.com/yanni-nadur/api-pedidos.git
cd api-pedidos
```

### 2️⃣ **Install dependencies**
```bash
composer install
```

### 3️⃣ **Configure the environment*
Rename the .env.example file to .env and set the database/JWT variables:
```
 database.default.hostname = localhost
 database.default.database = your_database
 database.default.username = your_user
 database.default.password = your_password

 JWT_SECRET_KEY = your_secret_key
```

### 4️⃣ **Run Migrations**
```bash
php spark migrate
```

### 5️⃣ ** Start the server**
```bash
php spark serve
```
The API will be available at: `http://localhost:8080`

## Endpoints

- **A Postman collection file is available on the project root!**
- **A [Swagger Editor](https://editor.swagger.io/) YAML file is available on the project root!**


### Authentication

#### Create Token (Required for any API method, using the Header: Key = Authorization and Value = Bearer YOUR_TOKEN)
- **POST** `/auth/login`

### Customers (`/customers`)

#### List customers
- **GET** `/customers`
- **Pagination parameters:** `page`, `per_page`
- **Example:** `GET /customers?page=1&per_page=10`
- **Filter parameters:** `name`, `cpf`, `created_at`, `updated_at`
- **Example:** `GET /customers?name=Joao&created_at=2025`

#### Create customer
- **POST** `/customers`

#### Get specific customer
- **GET** `/customers/{id}`

#### Update customer
- **PUT** `/customers/{id}`

#### Delete customer
- **DELETE** `/customers/{id}`

---

### Products (`/products`)

#### List products
- **GET** `/products`
- **Pagination parameters:** `page`, `per_page`
- **Example:** `GET /products?page=1&per_page=10`
- **Filter parameters:** `name`, `price`, `created_at`, `updated_at`
- **Example:** `GET /products?name=Water`

#### Create product
- **POST** `/products`

#### Get specific product
- **GET** `/products/{id}`

#### Update product
- **PUT** `/products/{id}`

#### Delete product
- **DELETE** `/products/{id}`

---

### Orders  (`/orders`)

#### List orders
- **GET** `/orders`
- **Pagination parameters:** `page`, `per_page`
- **Example:** `GET /orders?page=1&per_page=10`
- **Filter parameters:** `customer_id`, `status`, `created_at`, `updated_at`
- **Example:** `GET /orders?status=Pending`

#### Create order
- **POST** `/orders`

#### Get specific order
- **GET** `/orders/{id}`

#### Update order
- **PUT** `/orders/{id}`

#### Delete order
- **DELETE** `/orders/{id}`

---

