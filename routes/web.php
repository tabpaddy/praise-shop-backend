<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-email', function () {
    try {
        Mail::to('braydenegan7@gmail.com')->send(new TestMail());
        return 'Test email sent!';
    } catch (\Exception $e) {
        return 'Email failed: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')';
    }
});

use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);

Route::get('/payment/callback', [OrderController::class, 'handlePaymentCallback'])
    ->name('payment.callback');
