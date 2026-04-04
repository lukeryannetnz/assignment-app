<?php

declare(strict_types=1);

use App\Domains\Tenancy\Http\Controllers\OrganizationNodeController;
use App\Domains\Tenancy\Http\Controllers\OrganizationHierarchyImportController;
use App\Domains\Tenancy\Http\Controllers\OrganizationScopeController;
use App\Domains\Tenancy\Http\Controllers\PlatformTenantProvisioningController;
use App\Domains\Tenancy\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin/tenancy')->name('tenancy.admin.')->group(function () {
    Route::get('/tenants/create', [PlatformTenantProvisioningController::class, 'create'])->name('tenants.create');
    Route::post('/tenants', [PlatformTenantProvisioningController::class, 'store'])->name('tenants.store');
});

Route::middleware(['auth', 'tenant', 'admin'])->prefix('admin/tenancy')->name('tenancy.admin.')->group(function () {
    Route::get('/tenant', [TenantController::class, 'show'])->name('tenant.show');
    Route::put('/tenant', [TenantController::class, 'update'])->name('tenant.update');

    Route::get('/org-nodes', [OrganizationNodeController::class, 'index'])->name('org-nodes.index');
    Route::get('/org-nodes/{id}/scope', [OrganizationScopeController::class, 'show'])->name('org-nodes.scope.show');
    Route::post('/org-nodes/imports/dry-run', [OrganizationHierarchyImportController::class, 'dryRun'])
        ->name('org-nodes.imports.dry-run');
    Route::post('/org-nodes/imports', [OrganizationHierarchyImportController::class, 'commit'])
        ->name('org-nodes.imports.commit');
    Route::post('/org-nodes', [OrganizationNodeController::class, 'store'])->name('org-nodes.store');
    Route::put('/org-nodes/{id}', [OrganizationNodeController::class, 'update'])->name('org-nodes.update');
    Route::post('/org-nodes/{id}/move', [OrganizationNodeController::class, 'move'])
        ->name('org-nodes.move');
    Route::post('/org-nodes/{id}/deactivate', [OrganizationNodeController::class, 'deactivate'])
        ->name('org-nodes.deactivate');
    Route::post('/org-nodes/{id}/reactivate', [OrganizationNodeController::class, 'reactivate'])
        ->name('org-nodes.reactivate');
});
