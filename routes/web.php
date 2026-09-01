<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home')->name('home');

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::inertia('/', 'admin/Dashboard')->name('dashboard');
    });

require __DIR__.'/settings.php';
