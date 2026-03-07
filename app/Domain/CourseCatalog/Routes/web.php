<?php

declare(strict_types=1);

use App\Domain\CourseCatalog\Http\Controllers\AdminCourseController;
use App\Domain\CourseCatalog\Http\Controllers\CourseController;
use App\Domain\CourseCatalog\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('course-catalog::welcome');
})->name('course-catalog.welcome');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('course-catalog.dashboard');

Route::middleware(['auth', 'tenant'])->prefix('courses')->name('course-catalog.courses.')->group(function () {
    Route::get('/', [CourseController::class, 'index'])->name('index');
    Route::get('/{id}', [CourseController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'tenant', 'admin'])->prefix('admin/course-catalog/courses')
    ->name('course-catalog.admin.courses.')
    ->group(function () {
        Route::get('/', [AdminCourseController::class, 'index'])->name('index');
        Route::get('/create', [AdminCourseController::class, 'create'])->name('create');
        Route::post('/', [AdminCourseController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AdminCourseController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminCourseController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminCourseController::class, 'destroy'])->name('destroy');
    });
