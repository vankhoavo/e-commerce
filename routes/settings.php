<?php

use App\Http\Controllers\Auth\EmailPasswordResetController;
use App\Http\Controllers\Settings\EmailChangeController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Bảo mật tài khoản
|--------------------------------------------------------------------------
| Luồng quên mật khẩu được đặt cùng nhóm route Bảo mật để có một điểm vào
| thống nhất cho tài khoản TechStore. Các route này không dùng middleware
| guest vì trang quên mật khẩu phải mở được cả khi người dùng đã đăng nhập.
| Các route cũ trong web.php vẫn được giữ để không làm hỏng liên kết hiện có.
|--------------------------------------------------------------------------
*/
Route::get('settings/security/forgot-password', [EmailPasswordResetController::class, 'showRequest'])->name('security.password.request');
Route::post('settings/security/forgot-password', [EmailPasswordResetController::class, 'requestCode'])->middleware('throttle:3,5')->name('security.password.send');
Route::get('settings/security/forgot-password/verify', [EmailPasswordResetController::class, 'showVerify'])->name('security.password.verify');
Route::post('settings/security/forgot-password/verify', [EmailPasswordResetController::class, 'verifyCode'])->middleware('throttle:6,1')->name('security.password.verify.submit');
Route::post('settings/security/forgot-password/verify/resend', [EmailPasswordResetController::class, 'resendCode'])->middleware('throttle:3,5')->name('security.password.verify.resend');
Route::get('settings/security/forgot-password/reset', [EmailPasswordResetController::class, 'showReset'])->name('security.password.reset');
Route::post('settings/security/forgot-password/reset', [EmailPasswordResetController::class, 'resetPassword'])->middleware('throttle:6,1')->name('security.password.reset.submit');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('settings/email/request', [EmailChangeController::class, 'request'])->middleware('throttle:3,1')->name('email-change.request');
    Route::get('settings/email/verify', [EmailChangeController::class, 'edit'])->name('email-change.edit');
    Route::post('settings/email/verify', [EmailChangeController::class, 'verify'])->middleware('throttle:6,1')->name('email-change.verify');
    Route::post('settings/email/resend', [EmailChangeController::class, 'resend'])->middleware('throttle:3,1')->name('email-change.resend');
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
