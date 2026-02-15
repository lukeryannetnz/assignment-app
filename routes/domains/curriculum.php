<?php

declare(strict_types=1);

use App\Http\Controllers\Curriculum\CurriculumItemController;
use App\Http\Controllers\Curriculum\SectionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant', 'admin'])->prefix('admin')->name('admin.')->group(function () {
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
