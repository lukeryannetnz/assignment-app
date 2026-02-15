<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\CurriculumItemController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
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
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
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
});

require __DIR__ . '/auth.php';
