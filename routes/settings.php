<?php

use App\Http\Controllers\Settings\EmailChangeController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Teams\TeamController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Teams\TeamMemberController;
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
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Keep the four settings sections inside one styled Inertia page.
    Route::get('settings/security', function () {
        return redirect()->route('profile.edit', ['section' => 'security']);
    })->middleware(RequirePassword::class)->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])->middleware('throttle:6,1')->name('user-password.update');

    Route::get('settings/appearance', function () {
        return redirect()->route('profile.edit', ['section' => 'appearance']);
    })->name('appearance.edit');

    Route::get('settings/teams', function () {
        return redirect()->route('profile.edit', ['section' => 'teams']);
    })->name('teams.index');

    Route::post('settings/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::middleware(\App\Http\Middleware\EnsureTeamMembership::class)->group(function () {
        Route::get('settings/teams/{team}', [TeamController::class, 'edit'])->name('teams.edit');
        Route::patch('settings/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
        Route::delete('settings/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
        Route::post('settings/teams/{team}/switch', [TeamController::class, 'switch'])->name('teams.switch');
        Route::delete('settings/teams/{team}/leave', [TeamController::class, 'leave'])->name('teams.leave');
        Route::patch('settings/teams/{team}/members/{user}', [TeamMemberController::class, 'update'])->name('teams.members.update');
        Route::delete('settings/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('teams.members.destroy');
        Route::post('settings/teams/{team}/invitations', [TeamInvitationController::class, 'store'])->name('teams.invitations.store');
        Route::delete('settings/teams/{team}/invitations/{invitation}', [TeamInvitationController::class, 'destroy'])->name('teams.invitations.destroy');
    });
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}/decline', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json(['enroll' => route('security.edit'), 'manage' => route('security.edit')]);
})->name('well-known.passkeys');
