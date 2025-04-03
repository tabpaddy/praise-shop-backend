# Shop with Praise - E-commerce Backend (Laravel)

This is the **Laravel** backend API for the *Shop with Praise* e-commerce platform. It handles essential functionalities such as user authentication, product management, shopping cart operations, order processing, payments, and email notifications. Designed as a portfolio project, it provides a RESTful API to support the React frontend (available at [praise-shop frontend](https://github.com/tabpaddy/praise-shop)).

## Key Features

* **Authentication:** Secure user and admin authentication (login/logout) powered by Laravel Sanctum.
* **Product Management:** Comprehensive CRUD (Create, Read, Update, Delete) operations for products. Product creation, updates, and deletion are restricted to administrators.
* **Shopping Cart:** API endpoints for managing user shopping carts, including adding, viewing, and removing items.
* **Order Processing:** Functionality to place orders, process payments, and generate order receipts.
* **Payment Integration:** Support for multiple payment gateways:
    * Stripe API for credit card payments.
    * Paystack API for regional (specific country) payments.
    * Cash on Delivery (COD) option.
* **Email Notifications:** Automated email communication for order confirmations and password reset instructions, sent via Gmail SMTP.
* **Role-Based Authorization:** Implementation of user roles (Super Admin and Sub-Admin) to control access to specific functionalities, managed through middleware.

## Technologies

* **Framework:** Laravel 11 (requires PHP 8.2+)
* **Database:** MySQL (for structured data storage)
* **API Authentication:** Laravel Sanctum (for token-based API authentication)
* **Payment Gateways:**
    * Stripe API (using the `stripe/stripe-php` Composer package)
    * Paystack API
* **Email Service:** Gmail SMTP
* **CORS:** Cross-Origin Resource Sharing (configured for frontend connectivity)
* **Testing:** Laravel Tinker (for interactive command-line testing)
* **Dependency Management:** Composer

## Getting Started

### Prerequisites

* PHP 8.2+
* Composer (for PHP dependency management)
* MySQL (database server)
* Node.js and npm (only required if you're also setting up the React frontend - see the [frontend README](https://github.com/tabpaddy/praise-shop/README.md))

### Local Installation

1.  **Clone the Repository:**

    ```bash
    git clone [https://github.com/tabpaddy/praise-shop-backend.git](https://github.com/tabpaddy/praise-shop-backend.git)
    cd praise-shop-backend
    ```

2.  **Install Dependencies:**

    ```bash
    composer install
    ```

3.  **Configure Environment:**

    * Copy the example environment file:

        ```bash
        cp .env.example .env
        ```

    * Edit the `.env` file to provide your specific configuration:

        ```dotenv
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
        MAIL_PASSWORD=your-app-password # Use a Gmail App Password!
        MAIL_ENCRYPTION=tls
        MAIL_FROM_ADDRESS="noreply@shopwithpraise.com"
        MAIL_FROM_NAME="Shop with Praise"

        STRIPE_KEY=your-stripe-public-key
        STRIPE_SECRET=your-stripe-secret-key
        PAYSTACK_PUBLIC_KEY=your-paystack-public-key
        PAYSTACK_SECRET_KEY=your-paystack-secret-key
        PAYSTACK_PAYMENT_URL=[https://api.paystack.co](https://api.paystack.co)
        ```

4.  **Generate Application Key:**

    ```bash
    php artisan key:generate
    ```

5.  **Run Database Migrations:**

    ```bash
    php artisan migrate
    ```

6.  **Start the Development Server:**

    ```bash
    php artisan serve
    ```

    The server will be accessible at `http://localhost:8000`.

7.  **Test with Tinker (Optional):**

    ```bash
    php artisan tinker
    >>> User::create(['name' => 'Test User', 'email' => 'test@example.com', 'password' => bcrypt('password')]);
    ```

## API Endpoints

* All routes are prefixed with `/api/` and are protected by Laravel Sanctum authentication where indicated.

### Authentication

* `POST /api/register` - Register a new user (returns authentication token).
* `POST /api/login` - Authenticate a user and receive an authentication token.
* `POST /api/logout` - Invalidate the current user's authentication token (requires Sanctum authentication).
* `POST /api/forgot-password` - Initiate the password reset process by sending a reset link to the user's email.
* `POST /api/reset-password` - Reset the user's password using a valid reset token.

### Products

* `GET /api/products` - Retrieve a list of all products.
* `GET /api/products/{id}` - Retrieve details for a specific product.
* `POST /api/admin/products` - Create a new product (Admin authorization required).
* `PUT /api/admin/manage-products/{id}` - Update an existing product (Admin authorization required).
* `DELETE /api/admin/manage-products/{id}` - Delete a product (Admin authorization required).

### Cart

* `POST /api/add-to-cart` - Add an item to the shopping cart (supports optional Sanctum authentication for user-specific carts).
* `GET /api/cart/{cart_id}` - Retrieve the items in a shopping cart (supports optional Sanctum authentication).
* `PUT /api/cart/{id}` - Update the quantity of an item in the cart (supports optional Sanctum authentication).
* `DELETE /api/remove-item/{id}` - Remove an item from the cart (supports optional Sanctum authentication).

### Orders

* `POST /api/orders` - Place a new order (requires Sanctum authentication; supports Stripe, Paystack, and COD payments).
* `GET /api/orders` - Retrieve the order history for the authenticated user (requires Sanctum authentication).
* `PUT /api/orders/{id}` - Update the status of an order (Admin authorization required).

### Admin Management

* `POST /api/admin/create-subadmin` - Create a new sub-administrator account (Super Admin authorization required).
* `GET /api/admin/sub-admin` - Retrieve a list of all administrator accounts (Super Admin authorization required).
* `DELETE /api/admin/sub-admin/{id}` - Delete an administrator account (Super Admin authorization required).

### Role-Based Authorization

* **Super Admin:** Has full access to all API endpoints and administrative functions.
* **Sub Admin:** Has restricted access, typically limited to product and user management, but not administrator management.
* Authorization is enforced using middleware located in `app/Http/Middleware/` (e.g., `EnsureIsAdmin`).

### Configuration Notes

* **CORS Configuration:** Cross-Origin Resource Sharing is configured in `config/cors.php` to allow requests from `https://shopwithpraise.vercel.app` and `http://localhost:5173`.
* **Email Sending:** Order confirmation emails (`SendOrderJob`) and password reset emails (`SendResetPasswordMail`) are sent using Gmail SMTP. The queue driver is set to `sync` for immediate processing (`QUEUE_CONNECTION=sync`).
* **Payment Gateway Integration:** Stripe and Paystack are integrated using their respective PHP SDKs.

### Deployment

For production environments:

1.  Update the `.env` file with your live database credentials, SMTP settings, and payment gateway API keys.
2.  Optimize Laravel configuration:

    ```bash
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    ```

3.  Run database migrations (with caution in production):

    ```bash
    php artisan migrate --force
    ```

4.  Configure a web server (e.g., Apache or Nginx) to serve the Laravel application.

### Deployment Limitations

This project was built primarily for portfolio demonstration purposes with a limited budget. As such, it is designed to be run locally and paired with the frontend hosted at `https://shopwithpraise.vercel.app`.

### Lessons Learned

* Developed a robust RESTful API using Laravel Sanctum for secure authentication.
* Implemented a role-based authorization system to manage user permissions.
* Integrated Gmail SMTP for reliable email delivery.
* Worked with multiple payment gateways (Stripe and Paystack) within a Laravel application.
* Utilized Laravel Tinker for efficient testing and debugging.

### Contributing

Contributions are welcome! Please fork the repository and submit pull requests for any enhancements or bug fixes.

### License

This project is licensed under the MIT License (see `LICENSE`).

### Contact

* GitHub: [tabpaddy](https://github.com/tabpaddy)
* Email: [taborotap@gmail.com](mailto:taborotap@gmail.com)
