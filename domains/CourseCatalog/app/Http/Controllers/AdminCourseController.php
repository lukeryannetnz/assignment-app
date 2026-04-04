<?php

declare(strict_types=1);

namespace App\Domains\CourseCatalog\Http\Controllers;

use App\Domains\CourseCatalog\Services\CourseCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Nette\ArgumentOutOfRangeException;

class AdminCourseController
{
    public function __construct(private readonly CourseCatalogService $courseCatalogService)
    {
    }

    /**
     * Display a listing of courses.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }

        $courses = $this->courseCatalogService->paginateAdminCourses(
            tenantId: $user->tenant_id,
            perPage: 10,
            page: $request->integer('page', 1),
        );

        return view('course-catalog::admin.courses.index', ['courses' => $courses]);
    }

    /**
     * Show the form for creating a new course.
     */
    public function create(): View
    {
        return view('course-catalog::admin.courses.create');
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

        $this->courseCatalogService->createCourse($user->tenant_id, $validated['name'], $validated['description']);

        return redirect()->route('course-catalog.admin.courses.index')
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

        $course = $this->courseCatalogService->findAdminCourse($user->tenant_id, $id);
        $page = $request->query('page', '1');
        return view('course-catalog::admin.courses.edit', ['course' => $course, 'page' => $page]);
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
        $this->courseCatalogService->findAdminCourse($user->tenant_id, $id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $this->courseCatalogService->updateCourse($user->tenant_id, $id, $validated['name'], $validated['description']);

        $page = $request->input('page', '1');
        return redirect()->route('course-catalog.admin.courses.index', ['page' => $page])
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
        $this->courseCatalogService->deleteCourse($user->tenant_id, $id);

        return redirect()->route('course-catalog.admin.courses.index')
            ->with('success', 'Course deleted successfully!');
    }
}
