<?php

declare(strict_types=1);

use App\Http\Controllers\CourseCatalog\AdminCourseController;
use App\Http\Controllers\CourseCatalog\CourseController;
use App\Http\Controllers\CourseCatalog\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('course-catalog.welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('dashboard');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{id}', [CourseController::class, 'show'])->name('courses.show');
});

Route::middleware(['auth', 'tenant', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/courses', [AdminCourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/create', [AdminCourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [AdminCourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{id}/edit', [AdminCourseController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{id}', [AdminCourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{id}', [AdminCourseController::class, 'destroy'])->name('courses.destroy');
});
