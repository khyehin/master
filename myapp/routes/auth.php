<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PinSetupController;
use App\Http\Controllers\Auth\PinVerifyController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Register disabled — nobody can access (redirect GET, forbid POST)
    Route::get('register', function () {
        return redirect()->route('login');
    })->name('register');
    Route::post('register', function () {
        abort(403, 'Registration is disabled.');
    });

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Forgot password 關閉 — 誰都不可以存取
    Route::get('forgot-password', function () {
        return redirect()->route('login');
    })->name('password.request');
    Route::post('forgot-password', function () {
        abort(403, 'Password reset is disabled.');
    })->name('password.email');
    Route::get('reset-password/{token}', function () {
        return redirect()->route('login');
    })->name('password.reset');
    Route::post('reset-password', function () {
        abort(403, 'Password reset is disabled.');
    })->name('password.store');
});

Route::middleware('auth')->group(function () {
    // First login: force reset password, then PIN
    Route::get('password/first', [PasswordController::class, 'showFirst'])->name('password.first');
    Route::post('password/first', [PasswordController::class, 'storeFirst'])->name('password.first.store');

    Route::get('pin/verify', [PinVerifyController::class, 'show'])->name('pin.verify');
    Route::post('pin/verify', [PinVerifyController::class, 'verify']);
    Route::get('pin/setup', [PinSetupController::class, 'show'])->name('pin.setup');
    Route::post('pin/setup', [PinSetupController::class, 'store']);

    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
