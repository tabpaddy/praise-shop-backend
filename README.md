# E-commerce Backend

This is the **Laravel** backend for the e-commerce platform, handling authentication, product management, cart, orders, and payments.

## Features
- User Authentication (Laravel Sanctum)
- Product Management (CRUD)
- Shopping Cart API
- Order Management
- Payment Integration (Stripe & Bank Transfer)
- Secure API Endpoints

## Technologies Used
- Laravel 10 (PHP Framework)
- MySQL (Database)
- Sanctum (Authentication)
- Stripe API (Payment Processing)
- Paystack API (payment processing)
- MailTrap (Email Notifications)
- Cloudinary (Image Uploads)

## Installation & Setup

### Prerequisites
Ensure you have **PHP 8+, Composer, and MySQL** installed on your system.

### Steps to Run
```bash
# Clone the repository
git clone https://github.com/tabpaddy/ecommerce-backend.git
cd ecommerce-backend

# Install dependencies
composer install

# Copy the environment file and configure your database
cp .env.example .env

# Generate an application key
php artisan key:generate

# Configure your database in .env file
DB_DATABASE=ecommerce
DB_USERNAME=root
DB_PASSWORD=

# Run migrations and seed data
php artisan migrate --seed

# Install Laravel Passport for authentication
php artisan passport:install

# Serve the application
php artisan serve
```

## API Endpoints
### Authentication
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `POST /api/logout` - User logout

### Products
- `GET /api/products` - Get all products
- `GET /api/products/{id}` - Get single product details
- `POST /api/products` - Add a product (Admin)
- `PUT /api/products/{id}` - Update product (Admin)
- `DELETE /api/products/{id}` - Delete product (Admin)

### Cart
- `POST /api/cart/add` - Add product to cart
- `GET /api/cart` - View cart items
- `DELETE /api/cart/{id}` - Remove item from cart

### Orders
- `POST /api/order` - Place an order
- `GET /api/orders` - View order history

## Deployment
For production, update your `.env` file and set up a web server (Apache/Nginx). Run:
```bash
php artisan cache:clear
php artisan config:cache
php artisan migrate --force
```

## Contributing
Feel free to fork this repository and submit pull requests.

## License
This project is licensed under the MIT License.

