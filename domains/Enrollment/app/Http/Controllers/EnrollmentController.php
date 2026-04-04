<?php

declare(strict_types=1);

namespace App\Domains\Enrollment\Http\Controllers;

use App\Domains\CourseCatalog\Services\CourseCatalogService;
use App\Domains\Enrollment\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Nette\ArgumentOutOfRangeException;

class EnrollmentController
{
    public function __construct(
        private readonly CourseCatalogService $courseCatalogService,
        private readonly EnrollmentService $enrollmentService,
    ) {
    }

    /**
     * Enroll the authenticated user in a course.
     */
    public function enroll(Request $request, int $courseId): RedirectResponse
    {
        $user = $request->user();

        if ($user == null) {
            throw new ArgumentOutOfRangeException("User is required.");
        }
        if ($user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant is required.");
        }

        $course = $this->courseCatalogService->findCourse($user->tenant_id, $courseId);

        if ($this->enrollmentService->isUserEnrolled($user->tenant_id, (int) $user->id, $courseId)) {
            return redirect()->back()
                ->with('info', 'You are already enrolled in this course.');
        }

        $this->enrollmentService->enroll($user->tenant_id, (int) $user->id, $courseId);

        return redirect()->back()
            ->with('success', "You have successfully enrolled in {$course->name}!");
    }

    /**
     * Unenroll the authenticated user from a course.
     */
    public function unenroll(Request $request, int $courseId): RedirectResponse
    {
        $user = $request->user();

        if ($user == null) {
            throw new ArgumentOutOfRangeException("User is required.");
        }
        if ($user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant is required.");
        }

        $course = $this->courseCatalogService->findCourse($user->tenant_id, $courseId);

        $this->enrollmentService->unenroll($user->tenant_id, (int) $user->id, $courseId);

        return redirect()->back()
            ->with('success', "You have unenrolled from {$course->name}.");
    }
}
