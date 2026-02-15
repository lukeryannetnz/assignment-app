<?php

declare(strict_types=1);

namespace App\Http\Controllers\Enrollment;

use App\Models\CourseCatalog\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Nette\ArgumentOutOfRangeException;

class EnrollmentController
{
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

        $course = Course::where('tenant_id', $user->tenant_id)->findOrFail($courseId);

        if ($user->courses()->where('courses.id', $courseId)->exists()) {
            return redirect()->back()
                ->with('info', 'You are already enrolled in this course.');
        }

        $user->courses()->attach($courseId, ['tenant_id' => $user->tenant_id]);

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

        $course = Course::where('tenant_id', $user->tenant_id)->findOrFail($courseId);

        $user->courses()->detach($courseId);

        return redirect()->back()
            ->with('success', "You have unenrolled from {$course->name}.");
    }
}
