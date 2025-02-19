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


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/add-to-cart', [CartController::class, 'addToCart']);
    Route::get('/cart', [CartController::class, 'getCart']);
    Route::post('/merge-cart', [CartController::class, 'mergeCartAfterLogin']);
    Route::delete('/cart/{id}', [CartController::class, 'removeFromCart']);
    Route::delete('/cart/clear', [CartController::class, 'clearCart']);
});



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
});
