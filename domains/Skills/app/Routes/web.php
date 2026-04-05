<?php

declare(strict_types=1);

use App\Domains\Skills\Http\Controllers\RoleMappingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant', 'admin'])
    ->prefix('admin/skills/role-mappings')
    ->name('skills.admin.role-mappings.')
    ->group(function () {
        Route::get('/', [RoleMappingController::class, 'adminIndex'])->name('index');
        Route::post('/starter-library', [RoleMappingController::class, 'loadStarterLibrary'])
            ->name('starter-library.store');
        Route::post('/roles', [RoleMappingController::class, 'storeRole'])->name('roles.store');
        Route::post('/skills', [RoleMappingController::class, 'storeSkill'])->name('skills.store');
        Route::put('/{roleId}', [RoleMappingController::class, 'update'])->name('update');
        Route::post('/{roleId}/publish', [RoleMappingController::class, 'publish'])->name('publish');
    });

Route::middleware(['auth', 'tenant'])
    ->prefix('skills/role-mappings')
    ->name('skills.role-mappings.')
    ->group(function () {
        Route::get('/', [RoleMappingController::class, 'managerIndex'])->name('index');
    });
