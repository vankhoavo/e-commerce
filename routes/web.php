<?php

use App\Http\Controllers\Auth\EmailPasswordResetController;
use App\Http\Controllers\Auth\EmailVerificationOtpController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PayPalController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [ProductController::class, 'home'])->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/catalog', [ProductController::class, 'catalog'])->name('products.catalog');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/cart', fn () => Inertia::render('Cart'))->name('cart.index');
Route::get('/checkout', fn () => Inertia::render('Checkout'))->middleware('auth')->name('checkout.index');
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->middleware('guest')->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->middleware('guest')->name('google.callback');
Route::get('/auth/google/check-email', [GoogleAuthController::class, 'checkEmail'])->middleware('guest')->name('google.check-email');
Route::middleware('guest')->group(function (): void {
    Route::get('/forgot-password', [EmailPasswordResetController::class, 'showRequest'])->name('password.email.request');
    Route::post('/forgot-password', [EmailPasswordResetController::class, 'requestCode'])->middleware('throttle:3,5')->name('password.email.send');
    Route::get('/forgot-password/verify', [EmailPasswordResetController::class, 'showVerify'])->name('password.email.verify');
    Route::post('/forgot-password/verify', [EmailPasswordResetController::class, 'verifyCode'])->middleware('throttle:6,1')->name('password.email.verify.submit');
    Route::post('/forgot-password/verify/resend', [EmailPasswordResetController::class, 'resendCode'])->middleware('throttle:3,5')->name('password.email.verify.resend');
    Route::get('/forgot-password/reset', [EmailPasswordResetController::class, 'showReset'])->name('password.email.reset');
    Route::post('/forgot-password/reset', [EmailPasswordResetController::class, 'resetPassword'])->middleware('throttle:6,1')->name('password.email.reset.submit');
});
Route::middleware('auth')->group(function (): void {
    Route::get('/verify-email-otp', [EmailVerificationOtpController::class, 'show'])->name('email-verify-otp.show');
    Route::post('/verify-email-otp', [EmailVerificationOtpController::class, 'verify'])->middleware('throttle:6,1')->name('email-verify-otp.verify');
    Route::post('/verify-email-otp/resend', [EmailVerificationOtpController::class, 'resend'])->middleware('throttle:3,1')->name('email-verify-otp.resend');
    Route::post('/paypal/orders', [PayPalController::class, 'createOrder'])->middleware('throttle:10,1')->name('paypal.orders.create');
    Route::post('/paypal/orders/{orderId}/capture', [PayPalController::class, 'captureOrder'])->middleware('throttle:10,1')->name('paypal.orders.capture');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:10,1')->name('orders.store');
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::patch('/orders/{order}/return', [OrderController::class, 'returnOrder'])->name('orders.return');
});
Route::get('/dashboard', [DashboardController::class, '__invoke'])->middleware(['auth', 'verified'])->name('dashboard');
require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
