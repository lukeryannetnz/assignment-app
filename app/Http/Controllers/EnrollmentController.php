<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Nette\ArgumentOutOfRangeException;

class EnrollmentController extends Controller
{
    /**
     * Enroll the authenticated user in a course.
     */
    public function enroll(Request $request, int $courseId): RedirectResponse
    {
        $course = Course::findOrFail($courseId);
        $user = $request->user();

        if ($user == null) {
            throw new ArgumentOutOfRangeException("User is required.");
        }

        if ($user->courses()->where('course_id', $courseId)->exists()) {
            return redirect()->back()
                ->with('info', 'You are already enrolled in this course.');
        }

        $user->courses()->attach($courseId);

        return redirect()->back()
            ->with('success', "You have successfully enrolled in {$course->name}!");
    }

    /**
     * Unenroll the authenticated user from a course.
     */
    public function unenroll(Request $request, int $courseId): RedirectResponse
    {
        $course = Course::findOrFail($courseId);
        $user = $request->user();

        if ($user == null) {
            throw new ArgumentOutOfRangeException("User is required.");
        }

        $user->courses()->detach($courseId);

        return redirect()->back()
            ->with('success', "You have unenrolled from {$course->name}.");
    }
}
