<?php

declare(strict_types=1);

namespace App\Domains\Curriculum\Http\Controllers;

use App\Domains\Curriculum\Services\CurriculumService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Nette\ArgumentOutOfRangeException;

class SectionController
{
    public function __construct(private readonly CurriculumService $curriculumService)
    {
    }

    /**
     * Display sections for a course.
     */
    public function index(Request $request, int $courseId): View
    {
        $user = $request->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }

        $course = $this->curriculumService->findCourse($user->tenant_id, $courseId);
        $sections = $this->curriculumService->listSections($user->tenant_id, $courseId);

        return view('curriculum::admin.sections.index', [
            'course' => $course,
            'sections' => $sections,
        ]);
    }

    /**
     * Show the form for creating a new section.
     */
    public function create(int $courseId): View
    {
        $user = request()->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }
        $course = $this->curriculumService->findCourse($user->tenant_id, $courseId);

        return view('curriculum::admin.sections.create', ['course' => $course]);
    }

    /**
     * Store a newly created section.
     */
    public function store(Request $request, int $courseId): RedirectResponse
    {
        $user = $request->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
        ]);

        $this->curriculumService->createSection(
            $user->tenant_id,
            $courseId,
            $validated['title'],
            (int) $validated['order'],
        );

        return redirect()->route('curriculum.admin.sections.index', $courseId)
            ->with('success', 'Section created successfully!');
    }

    /**
     * Show the form for editing a section.
     */
    public function edit(int $courseId, int $id): View
    {
        $user = request()->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }
        $course = $this->curriculumService->findCourse($user->tenant_id, $courseId);
        $section = $this->curriculumService->findSection($user->tenant_id, $courseId, $id);

        return view('curriculum::admin.sections.edit', [
            'course' => $course,
            'section' => $section,
        ]);
    }

    /**
     * Update the specified section.
     */
    public function update(Request $request, int $courseId, int $id): RedirectResponse
    {
        $user = $request->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
        ]);

        $this->curriculumService->updateSection(
            $user->tenant_id,
            $courseId,
            $id,
            $validated['title'],
            (int) $validated['order'],
        );

        return redirect()->route('curriculum.admin.sections.index', $courseId)
            ->with('success', 'Section updated successfully!');
    }

    /**
     * Remove the specified section.
     */
    public function destroy(int $courseId, int $id): RedirectResponse
    {
        $user = request()->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }
        $this->curriculumService->deleteSection($user->tenant_id, $courseId, $id);

        return redirect()->route('curriculum.admin.sections.index', $courseId)
            ->with('success', 'Section deleted successfully!');
    }
}
