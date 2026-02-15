<?php

declare(strict_types=1);

namespace App\Http\Controllers\CourseCatalog;

use App\Models\CourseCatalog\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Nette\ArgumentOutOfRangeException;

class AdminCourseController
{
    /**
     * Display a listing of courses.
     */
    public function index(): View
    {
        $user = request()->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }

        $courses = Course::where('tenant_id', $user->tenant_id)
            ->withCount('users')
            ->paginate(10);
        return view('course-catalog.admin.courses.index', ['courses' => $courses]);
    }

    /**
     * Show the form for creating a new course.
     */
    public function create(): View
    {
        return view('course-catalog.admin.courses.create');
    }

    /**
     * Store a newly created course.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        Course::create(array_merge($validated, ['tenant_id' => $user->tenant_id]));

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course created successfully!');
    }

    /**
     * Show the form for editing a course.
     */
    public function edit(Request $request, int $id): View
    {
        $user = $request->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }

        $course = Course::where('tenant_id', $user->tenant_id)->findOrFail($id);
        $page = $request->query('page', '1');
        return view('course-catalog.admin.courses.edit', ['course' => $course, 'page' => $page]);
    }

    /**
     * Update the specified course.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }
        $course = Course::where('tenant_id', $user->tenant_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $course->update($validated);

        $page = $request->input('page', '1');
        return redirect()->route('admin.courses.index', ['page' => $page])
            ->with('success', 'Course updated successfully!');
    }

    /**
     * Remove the specified course.
     */
    public function destroy(int $id): RedirectResponse
    {
        $user = request()->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }
        $course = Course::where('tenant_id', $user->tenant_id)->findOrFail($id);
        $course->delete();

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course deleted successfully!');
    }
}
