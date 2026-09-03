<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/',[AdminController::class,'dashboard'])->middleware('admin.permission:dashboard')->name('dashboard');
    Route::get('/categories',[AdminController::class,'categories'])->middleware('admin.permission:categories')->name('categories');
    Route::post('/categories',[AdminController::class,'categoryStore'])->middleware('admin.permission:categories')->name('categories.store');
    Route::patch('/categories/{category}',[AdminController::class,'categoryUpdate'])->middleware('admin.permission:categories')->name('categories.update');
    Route::get('/products',[AdminController::class,'products'])->middleware('admin.permission:products')->name('products');
    Route::post('/products',[AdminController::class,'productStore'])->middleware('admin.permission:products')->name('products.store');
    Route::patch('/products/{product}',[AdminController::class,'productUpdate'])->middleware('admin.permission:products')->name('products.update');
    Route::patch('/products/{product}/toggle',[AdminController::class,'productToggle'])->middleware('admin.permission:products')->name('products.toggle');
    Route::get('/inventory',[AdminController::class,'inventory'])->middleware('admin.permission:inventory')->name('inventory');
    Route::get('/coupons',[AdminController::class,'coupons'])->middleware('admin.permission:coupons')->name('coupons');
    Route::post('/coupons',[AdminController::class,'couponStore'])->middleware('admin.permission:coupons')->name('coupons.store');
    Route::get('/shipping',[AdminController::class,'shipping'])->middleware('admin.permission:shipping')->name('shipping');
    Route::post('/shipping',[AdminController::class,'shippingStore'])->middleware('admin.permission:shipping')->name('shipping.store');
    Route::patch('/shipping/{shippingFee}',[AdminController::class,'shippingUpdate'])->middleware('admin.permission:shipping')->name('shipping.update');
    Route::get('/customers',fn(AdminController $controller)=>$controller->users('customer'))->middleware('admin.permission:customers')->name('customers');
    Route::post('/customers',fn(AdminController $controller,\Illuminate\Http\Request $request)=>$controller->userStore($request,'customer'))->middleware('admin.permission:customers')->name('customers.store');
    Route::get('/employees',fn(AdminController $controller)=>$controller->users('staff'))->middleware('admin.permission:employees')->name('employees');
    Route::post('/employees',fn(AdminController $controller,\Illuminate\Http\Request $request)=>$controller->userStore($request,'staff'))->middleware('admin.permission:employees')->name('employees.store');
    Route::patch('/users/{user}',[AdminController::class,'userUpdate'])->middleware('admin.permission:customers')->name('users.update');
    Route::delete('/users/{user}',[AdminController::class,'userDelete'])->middleware('admin.permission:customers')->name('users.delete');
    Route::get('/orders',[AdminController::class,'orders'])->middleware('admin.permission:orders')->name('orders');
    Route::delete('/orders',[AdminController::class,'ordersDeleteAll'])->middleware('admin.permission:orders')->throttle('10,1')->name('orders.delete-all');
    Route::patch('/orders/{order}/approve',[AdminController::class,'orderApprove'])->middleware('admin.permission:orders')->name('orders.approve');
    Route::patch('/orders/{order}',[AdminController::class,'orderUpdate'])->middleware('admin.permission:orders')->name('orders.update');
    Route::delete('/orders/{order}',[AdminController::class,'orderDelete'])->middleware('admin.permission:orders')->throttle('10,1')->name('orders.delete');
    Route::get('/administrators',[AdminController::class,'administrators'])->middleware('admin.permission:administrators')->name('administrators');
    Route::post('/administrators',[AdminController::class,'administratorStore'])->middleware('admin.permission:administrators')->name('administrators.store');
    Route::patch('/administrators/{user}',[AdminController::class,'administratorUpdate'])->middleware('admin.permission:administrators')->name('administrators.update');
    Route::patch('/administrators/{user}/toggle',[AdminController::class,'administratorToggle'])->middleware('admin.permission:administrators')->name('administrators.toggle');
    Route::delete('/administrators/{user}',[AdminController::class,'administratorDelete'])->middleware('admin.permission:administrators')->name('administrators.delete');
});
