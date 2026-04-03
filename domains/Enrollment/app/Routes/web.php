<?php

declare(strict_types=1);

use App\Domains\Enrollment\Http\Controllers\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant'])->prefix('courses/{courseId}')->name('enrollment.')->group(function () {
    Route::post('/enroll', [EnrollmentController::class, 'enroll'])->name('enroll');
    Route::delete('/unenroll', [EnrollmentController::class, 'unenroll'])->name('unenroll');
});
