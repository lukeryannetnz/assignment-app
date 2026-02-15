<?php

declare(strict_types=1);

use App\Http\Controllers\CourseCatalog\AdminCourseController;
use App\Http\Controllers\CourseCatalog\CourseController;
use App\Http\Controllers\Curriculum\CurriculumItemController;
use App\Http\Controllers\Curriculum\SectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Enrollment\EnrollmentController;
use App\Http\Controllers\IdentityAccess\AdminController;
use App\Http\Controllers\IdentityAccess\ProfileController;
use App\Http\Controllers\Tenancy\OrganizationNodeController;
use App\Http\Controllers\Tenancy\TenantController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('dashboard');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Student course browsing and enrollment
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{id}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('/courses/{courseId}/enroll', [EnrollmentController::class, 'enroll'])->name('courses.enroll');
    Route::delete('/courses/{courseId}/unenroll', [EnrollmentController::class, 'unenroll'])->name('courses.unenroll');
});

// Admin routes
Route::middleware(['auth', 'tenant', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // User management
    Route::get('/users', [AdminController::class, 'index'])->name('users.index');
    Route::post('/users/{id}/promote', [AdminController::class, 'promoteToAdmin'])->name('users.promote');
    Route::post('/users/{id}/demote', [AdminController::class, 'demoteFromAdmin'])->name('users.demote');

    // Course management
    Route::get('/courses', [AdminCourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/create', [AdminCourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [AdminCourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{id}/edit', [AdminCourseController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{id}', [AdminCourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{id}', [AdminCourseController::class, 'destroy'])->name('courses.destroy');

    // Section management
    Route::get('/courses/{courseId}/sections', [SectionController::class, 'index'])
        ->name('courses.sections.index');
    Route::get('/courses/{courseId}/sections/create', [SectionController::class, 'create'])
        ->name('courses.sections.create');
    Route::post('/courses/{courseId}/sections', [SectionController::class, 'store'])
        ->name('courses.sections.store');
    Route::get('/courses/{courseId}/sections/{id}/edit', [SectionController::class, 'edit'])
        ->name('courses.sections.edit');
    Route::put('/courses/{courseId}/sections/{id}', [SectionController::class, 'update'])
        ->name('courses.sections.update');
    Route::delete('/courses/{courseId}/sections/{id}', [SectionController::class, 'destroy'])
        ->name('courses.sections.destroy');

    // Curriculum item management (quizzes)
    Route::get('/sections/{sectionId}/items/create', [CurriculumItemController::class, 'create'])
        ->name('sections.items.create');
    Route::post('/sections/{sectionId}/items', [CurriculumItemController::class, 'store'])
        ->name('sections.items.store');
    Route::get('/sections/{sectionId}/items/{id}/edit', [CurriculumItemController::class, 'edit'])
        ->name('sections.items.edit');
    Route::put('/sections/{sectionId}/items/{id}', [CurriculumItemController::class, 'update'])
        ->name('sections.items.update');
    Route::delete('/sections/{sectionId}/items/{id}', [CurriculumItemController::class, 'destroy'])
        ->name('sections.items.destroy');

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
