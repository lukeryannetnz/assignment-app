<?php

declare(strict_types=1);

use App\Domain\Curriculum\Http\Controllers\CurriculumItemController;
use App\Domain\Curriculum\Http\Controllers\SectionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant', 'admin'])
    ->prefix('admin/curriculum')
    ->name('curriculum.admin.')
    ->group(function () {
        Route::get('/courses/{courseId}/sections', [SectionController::class, 'index'])
            ->name('sections.index');
        Route::get('/courses/{courseId}/sections/create', [SectionController::class, 'create'])
            ->name('sections.create');
        Route::post('/courses/{courseId}/sections', [SectionController::class, 'store'])
            ->name('sections.store');
        Route::get('/courses/{courseId}/sections/{id}/edit', [SectionController::class, 'edit'])
            ->name('sections.edit');
        Route::put('/courses/{courseId}/sections/{id}', [SectionController::class, 'update'])
            ->name('sections.update');
        Route::delete('/courses/{courseId}/sections/{id}', [SectionController::class, 'destroy'])
            ->name('sections.destroy');

        Route::get('/sections/{sectionId}/items/create', [CurriculumItemController::class, 'create'])
            ->name('items.create');
        Route::post('/sections/{sectionId}/items', [CurriculumItemController::class, 'store'])
            ->name('items.store');
        Route::get('/sections/{sectionId}/items/{id}/edit', [CurriculumItemController::class, 'edit'])
            ->name('items.edit');
        Route::put('/sections/{sectionId}/items/{id}', [CurriculumItemController::class, 'update'])
            ->name('items.update');
        Route::delete('/sections/{sectionId}/items/{id}', [CurriculumItemController::class, 'destroy'])
            ->name('items.destroy');
    });
