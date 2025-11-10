<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController
{
    /**
     * Display a listing of courses.
     */
    public function index(): View
    {
        $courses = Course::withCount('users')->paginate(10);
        return view('admin.courses.index', ['courses' => $courses]);
    }

    /**
     * Show the form for creating a new course.
     */
    public function create(): View
    {
        return view('admin.courses.create');
    }

    /**
     * Store a newly created course.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        Course::create($validated);

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course created successfully!');
    }

    /**
     * Show the form for editing a course.
     */
    public function edit(Request $request, int $id): View
    {
        $course = Course::findOrFail($id);
        $page = $request->query('page', '1');
        return view('admin.courses.edit', ['course' => $course, 'page' => $page]);
    }

    /**
     * Update the specified course.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $course = Course::findOrFail($id);

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
        $course = Course::findOrFail($id);
        $course->delete();

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course deleted successfully!');
    }
}
