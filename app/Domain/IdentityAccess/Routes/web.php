<?php

declare(strict_types=1);

use App\Domain\IdentityAccess\Http\Controllers\AdminController;
use App\Domain\IdentityAccess\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Domain\IdentityAccess\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Domain\IdentityAccess\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Domain\IdentityAccess\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Domain\IdentityAccess\Http\Controllers\Auth\NewPasswordController;
use App\Domain\IdentityAccess\Http\Controllers\Auth\PasswordController;
use App\Domain\IdentityAccess\Http\Controllers\Auth\PasswordResetLinkController;
use App\Domain\IdentityAccess\Http\Controllers\Auth\RegisteredUserController;
use App\Domain\IdentityAccess\Http\Controllers\Auth\VerifyEmailController;
use App\Domain\IdentityAccess\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('identity-access.auth.register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('identity-access.auth.login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('identity-access.auth.password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('identity-access.auth.password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('identity-access.auth.password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('identity-access.auth.password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('identity-access.auth.verification.notice');
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('identity-access.auth.verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('identity-access.auth.verification.send');
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('identity-access.auth.password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::put('password', [PasswordController::class, 'update'])->name('identity-access.auth.password.update');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('identity-access.auth.logout');
});

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('identity-access.profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('identity-access.profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('identity-access.profile.destroy');
});

Route::middleware(['auth', 'tenant', 'admin'])
    ->prefix('admin/identity-access')
    ->name('identity-access.admin.users.')
    ->group(function () {
        Route::get('/users', [AdminController::class, 'index'])->name('index');
        Route::post('/users/{id}/promote', [AdminController::class, 'promoteToAdmin'])
            ->name('promote');
        Route::post('/users/{id}/demote', [AdminController::class, 'demoteFromAdmin'])
            ->name('demote');
    });
