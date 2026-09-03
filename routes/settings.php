<?php

use App\Http\Controllers\Auth\EmailVerificationOtpController;
use App\Http\Controllers\Settings\EmailChangeController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('settings/email/request', [EmailChangeController::class, 'request'])->middleware('throttle:3,1')->name('email-change.request');
    Route::get('settings/email/verify', [EmailChangeController::class, 'edit'])->name('email-change.edit');
    Route::post('settings/email/verify', [EmailChangeController::class, 'verify'])->middleware('throttle:6,1')->name('email-change.verify');
    Route::post('settings/email/resend', [EmailChangeController::class, 'resend'])->middleware('throttle:3,1')->name('email-change.resend');
    Route::get('settings/orders', fn () => redirect()->route('profile.edit', ['section' => 'orders']))->name('settings.orders');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', function () {
        return redirect()->route('profile.edit', ['section' => 'security']);
    })->middleware(RequirePassword::class)->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])->middleware('throttle:6,1')->name('user-password.update');

    Route::get('settings/appearance', function () {
        return redirect()->route('profile.edit', ['section' => 'appearance']);
    })->name('appearance.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json(['enroll' => route('security.edit'), 'manage' => route('security.edit')]);
})->name('well-known.passkeys');
