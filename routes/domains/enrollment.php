<?php

declare(strict_types=1);

use App\Http\Controllers\Enrollment\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::post('/courses/{courseId}/enroll', [EnrollmentController::class, 'enroll'])->name('courses.enroll');
    Route::delete('/courses/{courseId}/unenroll', [EnrollmentController::class, 'unenroll'])->name('courses.unenroll');
});
