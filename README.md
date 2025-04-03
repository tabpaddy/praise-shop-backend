# Shop with Praise - E-commerce Backend

This is the **Laravel** backend for the *Shop with Praise* ecommerce platform, powering authentication, product management, cart operations, orders, payments, and email notifications. Built as a portfolio project, it provides a RESTful API for the React frontend (see [praise-shop frontend](https://github.com/tabpaddy/praise-shop)).

## Features
- **Authentication**: Secure user and admin login/logout with Laravel Sanctum.
- **Product Management**: Full CRUD operations for products (admin-only for create/update/delete).
- **Shopping Cart**: API for adding, viewing, and removing cart items.
- **Order Processing**: Place orders with payment options and email receipts.
- **Payment Integration**: Stripe (card payments), Paystack (regional payments), and Cash on Delivery (COD).
- **Email Notifications**: Order receipts and password reset links sent via Gmail SMTP.
- **Role-Based Authorization**: Super admin (full control) and sub-admins (limited actions) via admin role.

## Technologies Used
- **Framework**: Laravel 11 (PHP 8.2+)
- **Database**: MySQL (Schema storage)
- **Authentication**: Laravel Sanctum (Token-based API auth)
- **Payment Gateways**: 
  - Stripe API (Card payments)
  - Paystack API (Regional payments)
- **Email**: Gmail SMTP (Order receipts, password resets)
- **CORS**: Cross-Origin Resource Sharing for frontend connectivity
- **Testing**: Laravel Tinker (CLI testing)
- **Dependencies**: Composer-managed (e.g., `stripe/stripe-php`)

## Installation & Setup

### Prerequisites
- **PHP 8.2+**, **Composer**, and **MySQL** installed.
- Node.js and npm (for frontend integration—see [frontend README](https://github.com/tabpaddy/praise-shop/README.md)).

### Steps to Run Locally
1. **Clone the Repository**:
   ```bash
   git clone https://github.com/tabpaddy/praise-shop-backend.git
   cd praise-shop-backend

Install Dependencies:
bash

composer install

Configure Environment:
Copy .env.example to .env:
bash

cp .env.example .env

Edit .env with your settings:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@shopwithpraise.com"
MAIL_FROM_NAME="Shop with Praise"

STRIPE_KEY=your-stripe-public-key
STRIPE_SECRET=your-stripe-secret-key
PAYSTACK_PUBLIC_KEY=your-paystack-public-key
PAYSTACK_SECRET_KEY=your-paystack-secret-key
PAYSTACK_PAYMENT_URL=https://api.paystack.co

Generate app key:
bash

php artisan key:generate

Run Migrations:
bash

php artisan migrate

Start the Server:
bash

php artisan serve

Runs at http://localhost:8000.

Test with Tinker (Optional):
bash

php artisan tinker
>>> User::create(['name' => 'Test', 'email' => 'test@example.com', 'password' => bcrypt('password')]);

API Endpoints
All routes are prefixed with /api and protected by Sanctum where noted.
Authentication
POST /api/register - Register a user (returns token).

POST /api/login - Login user (returns token).

POST /api/logout - Logout user (Sanctum auth required).

POST /api/forget-password - Send password reset email.

POST /api/reset-password - Reset password with token.

Products
GET /api/products - List all products.

GET /api/products/{id} - Get product details.

POST /api/admin/products - Create product (Admin only, Sanctum auth).

PUT /api/admin/manage-products/{id} - Update product (Admin only, Sanctum auth).

DELETE /api/admin/manage-products/{id} - Delete product (Admin only, Sanctum auth).

Cart
POST /api/add-to-cart - Add item to cart (optional Sanctum auth middleware to accept cartId).

GET /api/cart/{cart_id} - View cart items (optional Sanctum auth middleware to accept cartId).

PUT /api/cart/{id} - Update cart item quantity (optional Sanctum auth middleware to accept cartId).

DELETE /api/remove-item/{id} - Remove item from cart (optional Sanctum auth middleware to accept cartId).

Orders
POST /api/orders - Place an order (Sanctum auth; supports Stripe, Paystack, COD).

GET /api/orders - View order history (Sanctum auth).

PUT /api/orders/{id} - Update order status (Admin only, Sanctum auth).

Admin Management
POST /api/admin/create-subadmin - Create sub-admin (Super Admin only, Sanctum auth).

GET /api/admin/sub-admin - List admins (Super Admin only, Sanctum auth).

DELETE /api/admin/sub-admin/{id} - Delete sub-admin (Super Admin only, Sanctum auth).

Role-Based Authorization
Super Admin: Full access to all endpoints (seeded manually or via Tinker).

Sub Admins: Restricted to product/user CRUD, no admin management.

Middleware checks roles in app/Http/Middleware/ (e.g., EnsureIsAdmin).

Configuration Details
CORS: Set in config/cors.php to allow https://shopwithpraise.vercel.app.

Emails: Gmail SMTP sends order receipts (SendOrderJob) and password reset links (SendResetPasswordMail) synchronously (QUEUE_CONNECTION=sync).

Payments: Stripe and Paystack integrated via their PHP SDKs.

Deployment
For production:
Update .env with live credentials (DB, SMTP, payment keys).

Cache configs:
bash

php artisan config:cache
php artisan migrate --force

Serve with Apache/Nginx (e.g., on a VPS).

Why No Live Hosting?
Built for resume purposes with a $0 budget. Runs locally; pair with the frontend at https://shopwithpraise.vercel.app.
Lessons Learned
Developed a RESTful API with Laravel Sanctum for secure authentication.

Implemented role-based authorization for admin workflows.

Integrated Gmail SMTP for synchronous email delivery.

Handled multiple payment gateways (Stripe, Paystack) with Laravel.

Used Tinker for rapid testing and debugging.

Contributing
Fork and submit pull requests—open to enhancements!
License
MIT License (LICENSE)
Contact
GitHub: tabpaddy

Email: taborotap@gmail.com (mailto:taborotap@gmail.com)

