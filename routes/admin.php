<?php

use App\Http\Controllers\AccountDeletionRequestController;
use App\Http\Controllers\AccountRecoveryRequestController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\AdministratorSecurityController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\IpCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/admin', [AdminController::class, 'dashboard'])->middleware(['auth', 'backoffice'])->name('admin.dashboard');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/categories', [AdminController::class, 'categories'])->middleware('admin.permission:categories')->name('categories');
    Route::post('/categories', [AdminController::class, 'categoryStore'])->middleware('admin.permission:categories')->name('categories.store');
    Route::patch('/categories/{category}', [AdminController::class, 'categoryUpdate'])->middleware('admin.permission:categories')->name('categories.update');
    Route::get('/products', [AdminController::class, 'products'])->middleware('admin.permission:products')->name('products');
    Route::post('/products', [AdminController::class, 'productStore'])->middleware('admin.permission:products')->name('products.store');
    Route::patch('/products/{product}', [AdminController::class, 'productUpdate'])->middleware('admin.permission:products')->name('products.update');
    Route::patch('/products/{product}/toggle', [AdminController::class, 'productToggle'])->middleware('admin.permission:products')->name('products.toggle');
    Route::get('/inventory', [InventoryController::class, 'index'])->middleware('admin.permission:inventory')->name('inventory');
    Route::get('/coupons', [AdminController::class, 'coupons'])->middleware('admin.permission:coupons')->name('coupons');
    Route::post('/coupons', [AdminController::class, 'couponStore'])->middleware('admin.permission:coupons')->name('coupons.store');
    Route::get('/shipping', [AdminController::class, 'shipping'])->middleware('admin.permission:shipping')->name('shipping');
    Route::post('/shipping', [AdminController::class, 'shippingStore'])->middleware('admin.permission:shipping')->name('shipping.store');
    Route::patch('/shipping/{shippingFee}', [AdminController::class, 'shippingUpdate'])->middleware('admin.permission:shipping')->name('shipping.update');
    Route::get('/customers', [CustomerController::class, 'index'])->middleware('admin.permission:customers')->name('customers');
    Route::patch('/customers/{user}', [CustomerController::class, 'update'])->middleware('admin.permission:customers')->name('customers.update');
    Route::patch('/customers/{user}/toggle', [CustomerController::class, 'toggle'])->middleware('admin.permission:customers')->name('customers.toggle');
    Route::patch('/customers/{user}/verify', [CustomerController::class, 'verify'])->middleware('admin.permission:customers')->name('customers.verify');
    Route::get('/orders', [AdminController::class, 'orders'])->middleware('admin.permission:orders')->name('orders');
    Route::delete('/orders', [AdminController::class, 'ordersDeleteAll'])->middleware(['admin.permission:orders','throttle:10,1'])->name('orders.delete-all');
    Route::patch('/orders/{order}/approve', [AdminController::class, 'orderApprove'])->middleware('admin.permission:orders')->name('orders.approve');
    Route::patch('/orders/{order}/status', [AdminController::class, 'orderStatusUpdate'])->middleware('admin.permission:orders')->name('orders.status');
    Route::patch('/orders/{order}', [AdminController::class, 'orderUpdate'])->middleware('admin.permission:orders')->name('orders.update');
    Route::delete('/orders/{order}', [AdminController::class, 'orderDelete'])->middleware(['admin.permission:orders','throttle:10,1'])->name('orders.delete');
    Route::get('/administrators', [AdministratorController::class, 'index'])->middleware('admin.permission:administrators')->name('administrators');
    Route::post('/administrators', [AdministratorController::class, 'store'])->middleware('admin.permission:administrators')->name('administrators.store');
    Route::post('/administrators/employees', [AdministratorController::class, 'storeStaff'])->middleware('admin.permission:administrators')->name('administrators.employees.store');
    Route::post('/administrators/senior-staff', [AdministratorController::class, 'storeSenior'])->middleware('admin.permission:administrators')->name('administrators.senior.store');
    Route::patch('/administrators/senior-staff/{user}', [AdministratorController::class, 'updateSenior'])->middleware('admin.permission:administrators')->name('administrators.senior.update');
    Route::patch('/administrators/senior-staff/{user}/toggle', [AdministratorController::class, 'toggleSenior'])->middleware('admin.permission:administrators')->name('administrators.senior.toggle');
    Route::delete('/administrators/senior-staff/{user}', [AdministratorController::class, 'destroySenior'])->middleware('admin.permission:administrators')->name('administrators.senior.delete');
    Route::patch('/administrators/{user}', [AdministratorController::class, 'update'])->middleware('admin.permission:administrators')->name('administrators.update');
    Route::patch('/administrators/{user}/toggle', [AdministratorController::class, 'toggle'])->middleware('admin.permission:administrators')->name('administrators.toggle');
    Route::delete('/administrators/{user}', [AdministratorController::class, 'destroy'])->middleware('admin.permission:administrators')->name('administrators.delete');
    Route::get('/account-deletion-requests', [AccountDeletionRequestController::class, 'index'])->middleware('admin.permission:customers')->name('account-deletion-requests');
    Route::patch('/account-deletion-requests/{deletionRequest}/approve', [AccountDeletionRequestController::class, 'approve'])->middleware('admin.permission:customers')->name('account-deletion-requests.approve');
    Route::patch('/account-deletion-requests/{deletionRequest}/reject', [AccountDeletionRequestController::class, 'reject'])->middleware('admin.permission:customers')->name('account-deletion-requests.reject');
    Route::get('/ip-check', [IpCheckController::class, 'index'])->name('ip-check');
    Route::post('/ip-check/lookup', [IpCheckController::class, 'lookup'])->middleware('throttle:30,1')->name('ip-check.lookup');
});

Route::middleware(['auth', 'root.admin'])->prefix('admin')->name('admin.security.')->group(function (): void {
    Route::get('/administrator/password/forgot', [AdministratorSecurityController::class, 'showPasswordReset'])->name('password.request');
    Route::get('/administrator/password/verify', [AdministratorSecurityController::class, 'showPasswordVerify'])->name('password.verify');
    Route::post('/administrator/password/verify', [AdministratorSecurityController::class, 'verifyPassword'])->middleware('throttle:6,1')->name('password.verify.submit');
    Route::get('/administrator/password/reset', [AdministratorSecurityController::class, 'showPasswordForm'])->name('password.reset');
    Route::post('/administrator/password/reset', [AdministratorSecurityController::class, 'resetPassword'])->middleware('throttle:6,1')->name('password.reset.submit');
    Route::get('/administrator/email/edit', [AdministratorSecurityController::class, 'editEmail'])->name('email.edit');
    Route::patch('/administrator/email', [AdministratorSecurityController::class, 'updateEmail'])->middleware('throttle:6,1')->name('email.update');
});

Route::middleware(['auth', 'backoffice'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::patch('/employees/{user}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{user}', [EmployeeController::class, 'delete'])->name('employees.delete');
    Route::patch('/employees/{user}/toggle', [EmployeeController::class, 'toggle'])->name('employees.toggle');
    Route::patch('/employees/{user}/approve', [EmployeeController::class, 'approve'])->name('employees.approve');
    Route::patch('/employees/{user}/reject', [EmployeeController::class, 'reject'])->name('employees.reject');
    Route::get('/account-recovery-requests', [AccountRecoveryRequestController::class, 'index'])->name('account-recovery-requests');
    Route::patch('/account-recovery-requests/{recovery}/approve', [AccountRecoveryRequestController::class, 'approve'])->name('account-recovery-requests.approve');
    Route::patch('/account-recovery-requests/{recovery}/reject', [AccountRecoveryRequestController::class, 'reject'])->name('account-recovery-requests.reject');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/account/deletion-request', [AccountDeletionRequestController::class, 'store'])->name('account.deletion-request');
});
