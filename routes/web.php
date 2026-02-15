<?php

declare(strict_types=1);

use App\Http\Controllers\Enrollment\EnrollmentController;
use App\Http\Controllers\Tenancy\OrganizationNodeController;
use App\Http\Controllers\Tenancy\TenantController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('course-catalog.welcome');
});

require __DIR__ . '/domains/course-catalog.php';
require __DIR__ . '/domains/curriculum.php';
require __DIR__ . '/domains/identity-access.php';

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::post('/courses/{courseId}/enroll', [EnrollmentController::class, 'enroll'])->name('courses.enroll');
    Route::delete('/courses/{courseId}/unenroll', [EnrollmentController::class, 'unenroll'])->name('courses.unenroll');
});

Route::middleware(['auth', 'tenant', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Tenant administration
    Route::get('/tenant', [TenantController::class, 'show'])->name('tenant.show');
    Route::put('/tenant', [TenantController::class, 'update'])->name('tenant.update');

    // Organization hierarchy administration
    Route::get('/org-nodes', [OrganizationNodeController::class, 'index'])->name('org-nodes.index');
    Route::post('/org-nodes', [OrganizationNodeController::class, 'store'])->name('org-nodes.store');
    Route::put('/org-nodes/{id}', [OrganizationNodeController::class, 'update'])->name('org-nodes.update');
    Route::post('/org-nodes/{id}/move', [OrganizationNodeController::class, 'move'])
        ->name('org-nodes.move');
    Route::post('/org-nodes/{id}/deactivate', [OrganizationNodeController::class, 'deactivate'])
        ->name('org-nodes.deactivate');
    Route::post('/org-nodes/{id}/reactivate', [OrganizationNodeController::class, 'reactivate'])
        ->name('org-nodes.reactivate');
});

require __DIR__ . '/auth.php';
