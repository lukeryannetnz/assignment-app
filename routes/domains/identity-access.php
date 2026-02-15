<?php

declare(strict_types=1);

use App\Http\Controllers\IdentityAccess\AdminController;
use App\Http\Controllers\IdentityAccess\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'tenant', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminController::class, 'index'])->name('users.index');
    Route::post('/users/{id}/promote', [AdminController::class, 'promoteToAdmin'])->name('users.promote');
    Route::post('/users/{id}/demote', [AdminController::class, 'demoteFromAdmin'])->name('users.demote');
});
