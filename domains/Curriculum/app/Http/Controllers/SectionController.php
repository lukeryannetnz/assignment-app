<?php

declare(strict_types=1);

namespace App\Domains\Curriculum\Http\Controllers;

use App\Domains\CourseCatalog\Models\Course;
use App\Domains\Curriculum\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Nette\ArgumentOutOfRangeException;

class SectionController
{
    /**
     * Display sections for a course.
     */
    public function index(int $courseId): View
    {
        $user = request()->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }

        $course = Course::where('tenant_id', $user->tenant_id)->findOrFail($courseId);
        $sections = $course->sections()
            ->where('tenant_id', $user->tenant_id)
            ->with('curriculumItems')
            ->get();

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
        $course = Course::where('tenant_id', $user->tenant_id)->findOrFail($courseId);

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
        $course = Course::where('tenant_id', $user->tenant_id)->findOrFail($courseId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
        ]);

        $course->sections()->create(array_merge($validated, ['tenant_id' => $user->tenant_id]));

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
        $course = Course::where('tenant_id', $user->tenant_id)->findOrFail($courseId);
        $section = Section::where('course_id', $courseId)
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

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
        Course::where('tenant_id', $user->tenant_id)->findOrFail($courseId);
        $section = Section::where('course_id', $courseId)
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
        ]);

        $section->update($validated);

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
        Course::where('tenant_id', $user->tenant_id)->findOrFail($courseId);
        $section = Section::where('course_id', $courseId)
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);
        $section->delete();

        return redirect()->route('curriculum.admin.sections.index', $courseId)
            ->with('success', 'Section deleted successfully!');
    }
}
