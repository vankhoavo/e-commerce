<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/categories', [AdminController::class, 'categories'])->name('categories');
    Route::post('/categories', [AdminController::class, 'categoryStore'])->name('categories.store');
    Route::patch('/categories/{category}', [AdminController::class, 'categoryUpdate'])->name('categories.update');
    Route::get('/products', [AdminController::class, 'products'])->name('products');
    Route::post('/products', [AdminController::class, 'productStore'])->name('products.store');
    Route::patch('/products/{product}', [AdminController::class, 'productUpdate'])->name('products.update');
    Route::patch('/products/{product}/toggle', [AdminController::class, 'productToggle'])->name('products.toggle');
    Route::get('/inventory', [AdminController::class, 'inventory'])->name('inventory');
    Route::get('/coupons', [AdminController::class, 'coupons'])->name('coupons');
    Route::post('/coupons', [AdminController::class, 'couponStore'])->name('coupons.store');
    Route::get('/shipping', [AdminController::class, 'shipping'])->name('shipping');
    Route::post('/shipping', [AdminController::class, 'shippingStore'])->name('shipping.store');
    Route::patch('/shipping/{shippingFee}', [AdminController::class, 'shippingUpdate'])->name('shipping.update');
    Route::get('/customers', fn (AdminController $controller) => $controller->users('customer'))->name('customers');
    Route::post('/customers', fn (AdminController $controller, \Illuminate\Http\Request $request) => $controller->userStore($request, 'customer'))->name('customers.store');
    Route::get('/employees', fn (AdminController $controller) => $controller->users('staff'))->name('employees');
    Route::post('/employees', fn (AdminController $controller, \Illuminate\Http\Request $request) => $controller->userStore($request, 'staff'))->name('employees.store');
    Route::patch('/users/{user}', [AdminController::class, 'userUpdate'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'userDelete'])->name('users.delete');
    Route::get('/orders', [AdminController::class, 'orders'])->name('orders');
    Route::patch('/orders/{order}/approve', [AdminController::class, 'orderApprove'])->name('orders.approve');
});
