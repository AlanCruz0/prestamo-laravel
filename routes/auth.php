<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerificationCodeController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
                ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('verify-code', [VerificationCodeController::class, 'show'])
                ->name('code-verification.notice');

    Route::post('verify-code', [VerificationCodeController::class, 'submit'])
                ->middleware('throttle:6,1')
                ->name('code-verification.verify');

    Route::post('verify-code/resend', [VerificationCodeController::class, 'resend'])
                ->middleware('throttle:3,1')
                ->name('code-verification.resend');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
});
