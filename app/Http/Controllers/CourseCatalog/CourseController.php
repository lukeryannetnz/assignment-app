<?php

declare(strict_types=1);

namespace App\Http\Controllers\CourseCatalog;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Nette\ArgumentOutOfRangeException;

class CourseController
{
    /**
     * Display a listing of courses for students to browse and enroll.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        if ($user == null) {
            throw new ArgumentOutOfRangeException("User is required.");
        }
        if ($user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant is required.");
        }
        $courses = Course::where('tenant_id', $user->tenant_id)->withCount('users')->paginate(4);

        // Get IDs of courses the user is enrolled in
        $enrolledCourseIds = $user->courses()
            ->where('courses.tenant_id', $user->tenant_id)
            ->pluck('courses.id')
            ->toArray();

        return view('course-catalog.courses.index', [
            'courses' => $courses,
            'enrolledCourseIds' => $enrolledCourseIds,
        ]);
    }

    /**
     * Display the specified course details.
     */
    public function show(Request $request, int $id): View
    {
        $user = $request->user();
        if ($user == null) {
            throw new ArgumentOutOfRangeException("User is required.");
        }
        if ($user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant is required.");
        }

        $course = Course::where('tenant_id', $user->tenant_id)
            ->withCount('users')
            ->findOrFail($id);

        $isEnrolled = $user->courses()->where('courses.id', $id)->exists();

        return view('course-catalog.courses.show', [
            'course' => $course,
            'isEnrolled' => $isEnrolled,
        ]);
    }
}
