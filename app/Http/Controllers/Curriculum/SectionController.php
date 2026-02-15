<?php

declare(strict_types=1);

namespace App\Http\Controllers\Curriculum;

use App\Models\Course;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectionController
{
    /**
     * Display sections for a course.
     */
    public function index(int $courseId): View
    {
        $course = Course::findOrFail($courseId);
        $sections = $course->sections()->with('curriculumItems')->get();

        return view('curriculum.admin.sections.index', [
            'course' => $course,
            'sections' => $sections,
        ]);
    }

    /**
     * Show the form for creating a new section.
     */
    public function create(int $courseId): View
    {
        $course = Course::findOrFail($courseId);

        return view('curriculum.admin.sections.create', ['course' => $course]);
    }

    /**
     * Store a newly created section.
     */
    public function store(Request $request, int $courseId): RedirectResponse
    {
        $course = Course::findOrFail($courseId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
        ]);

        $course->sections()->create($validated);

        return redirect()->route('admin.courses.sections.index', $courseId)
            ->with('success', 'Section created successfully!');
    }

    /**
     * Show the form for editing a section.
     */
    public function edit(int $courseId, int $id): View
    {
        $course = Course::findOrFail($courseId);
        $section = Section::where('course_id', $courseId)->findOrFail($id);

        return view('curriculum.admin.sections.edit', [
            'course' => $course,
            'section' => $section,
        ]);
    }

    /**
     * Update the specified section.
     */
    public function update(Request $request, int $courseId, int $id): RedirectResponse
    {
        Course::findOrFail($courseId);
        $section = Section::where('course_id', $courseId)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
        ]);

        $section->update($validated);

        return redirect()->route('admin.courses.sections.index', $courseId)
            ->with('success', 'Section updated successfully!');
    }

    /**
     * Remove the specified section.
     */
    public function destroy(int $courseId, int $id): RedirectResponse
    {
        Course::findOrFail($courseId);
        $section = Section::where('course_id', $courseId)->findOrFail($id);
        $section->delete();

        return redirect()->route('admin.courses.sections.index', $courseId)
            ->with('success', 'Section deleted successfully!');
    }
}
