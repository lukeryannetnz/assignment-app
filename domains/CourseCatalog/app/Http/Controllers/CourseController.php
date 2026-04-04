<?php

declare(strict_types=1);

namespace App\Domains\CourseCatalog\Http\Controllers;

use App\Domains\CourseCatalog\Services\CourseCatalogService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Nette\ArgumentOutOfRangeException;

class CourseController
{
    public function __construct(private readonly CourseCatalogService $courseCatalogService)
    {
    }

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
        $courses = $this->courseCatalogService->paginateStudentCourses(
            tenantId: $user->tenant_id,
            perPage: 4,
            page: $request->integer('page', 1),
        );
        $enrolledCourseIds = $this->courseCatalogService->enrolledCourseIds($user->tenant_id, (int) $user->id);

        return view('course-catalog::courses.index', [
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

        $course = $this->courseCatalogService->findCourse($user->tenant_id, $id);
        $isEnrolled = $this->courseCatalogService->isUserEnrolled($user->tenant_id, (int) $user->id, $id);

        return view('course-catalog::courses.show', [
            'course' => $course,
            'isEnrolled' => $isEnrolled,
        ]);
    }
}
