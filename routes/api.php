<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\Admin\AuthAdminController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/subscribe', [SubscribeController::class, "subscribe"]);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendRestLink']);
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);


Route::post('/admin/login', [AuthAdminController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/admin/logout', [AuthAdminController::class, 'logout']);
    Route::post('/admin/create-subadmin', [AuthAdminController::class, 'createSubAdmin']);
});
