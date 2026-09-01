<?php

use App\Http\Controllers\Auth\EmailVerificationOtpController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home')->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->middleware('guest')->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->middleware('guest')->name('google.callback');

Route::middleware('auth')->group(function (): void {
    Route::get('/verify-email-otp', [EmailVerificationOtpController::class, 'show'])->name('email-verify-otp.show');
    Route::post('/verify-email-otp', [EmailVerificationOtpController::class, 'verify'])->middleware('throttle:6,1')->name('email-verify-otp.verify');
    Route::post('/verify-email-otp/resend', [EmailVerificationOtpController::class, 'resend'])->middleware('throttle:3,1')->name('email-verify-otp.resend');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::inertia('/', 'admin/Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
