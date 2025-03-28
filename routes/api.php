<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\Admin\AuthAdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ManageUserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaystackController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StripeController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/subscribe', [SubscribeController::class, "subscribe"]);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendRestLink']);
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);
Route::get('/get_bestseller', [ProductController::class, 'getBestSellers']);
Route::get('/get_latest_collection', [ProductController::class, 'getHomeProduct']);
Route::get('/collection', [ProductController::class, 'getAllCollection']);
Route::get('/category', [CategoryController::class, 'getCollectionCategory']);
Route::get('/sub_category', [SubCategoryController::class, 'getCollectionSubCategory']);
Route::get('/single-product/{id}', [ProductController::class, 'getSingleUserProduct']);
Route::get('/liked-product/{id}', [ProductController::class, 'getLikedProduct']);
Route::get('/search', [SearchController::class, 'searchProducts']);
Route::post('/add-to-cart', [CartController::class, 'addToCart'])->middleware(\App\Http\Middleware\OptionalSanctumAuth::class);
Route::post('/count-cart', [CartController::class, 'countCart'])->middleware(\App\Http\Middleware\OptionalSanctumAuth::class);
Route::get('/cart/{cartId}', [CartController::class, 'getCart'])->middleware(\App\Http\Middleware\OptionalSanctumAuth::class);
Route::delete('/remove-item/{id}', [CartController::class, 'removeFromCart'])->middleware(\App\Http\Middleware\OptionalSanctumAuth::class);
Route::post('/paystack/webhook', [PaystackController::class, 'handleWebhook']);
Route::post('/stripe/webhook', [StripeController::class, 'handleWebhook']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/merge-cart', [CartController::class, 'mergeCartAfterLogin']);
    Route::delete('/cart/clear', [CartController::class, 'clearCart']);
    Route::post('/payment-order', [OrderController::class, 'store']);
    Route::post('/payment-callback', [OrderController::class, 'handlePaymentCallback']);
    Route::get('/delivery-information', [OrderController::class, 'deliveryInformation']);
    Route::get('/user-order', [OrderController::class, 'getUserOrder']);
    Route::post('/cancel-order', [OrderController::class, 'cancelOrder']);
});

// Route::get('/test-env', function () {
//     // if (file_exists(base_path('.env'))) {
//     //     $dotenv = \Dotenv\Dotenv::createImmutable(base_path());
//     //     $dotenv->load();
//     //     \Log::info('.env file manually loaded in test route');
//     // } else {
//     //     \Log::error('.env file not found in test route');
//     // }
//     return response()->json([
//         'stripe_key' => env('STRIPE_KEY'),
//         'stripe_secret' => env('STRIPE_SECRET'),
//         'stripe_webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
//         'frontend_url' => env('FRONTEND_URL'), // If you have this in .env
//     ]);
// });



Route::post('/admin/login', [AuthAdminController::class, 'login']);
Route::middleware('auth:admin')->group(function () {
    Route::post('/admin/logout', [AuthAdminController::class, 'logout']);
    Route::post('/admin/create-subadmin', [AuthAdminController::class, 'createSubAdmin']);
    Route::get('/admin/sub-admin', [AuthAdminController::class, 'subAdmin']);
    Route::delete('/admin/sub-admin/{delete}', [AuthAdminController::class, 'deleteSubAdmin']);
    Route::post('/add-user', [ManageUserController::class, 'addUser']);
    Route::get('/admin/manage-user', [ManageUserController::class, 'user']);
    Route::delete('/admin/manage-user/{delete}', [ManageUserController::class, 'deleteUser']);
    Route::post('/admin/add-category', [CategoryController::class, 'addCategory']);
    Route::get('/admin/manage-category', [CategoryController::class, 'getAllCategory']);
    Route::get('/admin/manage-user-count', [ManageUserController::class, 'countUser']);
    Route::delete('/admin/manage-category/{delete}', [CategoryController::class, 'deleteCategory']);
    Route::put('/admin/edit-category/{edit}', [CategoryController::class, 'editCategory']);
    Route::post('/admin/add-sub-category', [SubCategoryController::class, 'addSubCategory']);
    Route::get('/admin/manage-sub-category', [SubCategoryController::class, 'getAllSubCategory']);
    Route::delete('admin/manage-sub-category/{delete}', [SubCategoryController::class, 'deleteSubCategory']);
    Route::put('/admin/edit-sub-category/{edit}', [SubCategoryController::class, 'editSubCategory']);
    Route::post('/admin/add-product', [ProductController::class, 'addProduct']);
    Route::get('/admin/manage-product', [ProductController::class, 'getAllProduct']);
    Route::delete('/admin/manage-product/{delete}', [ProductController::class, 'deleteProduct']);
    Route::get('/admin/manage-product/{id}', [ProductController::class, 'getSingleProduct']);
    Route::put('/admin/update-product/{id}', [ProductController::class, 'updateProduct']);
    Route::get('/admin/manage-product-count', [ProductController::class, 'countProduct']);
    Route::get('/admin/order', [OrderController::class, 'getAllOrder']);
    Route::get('/admin/order-count', [OrderController::class, 'countOrder']);
    Route::post('/admin/orders/status', [OrderController::class, 'status']);
    Route::post('/admin/orders/payment', [OrderController::class, 'payment_status']);
});
