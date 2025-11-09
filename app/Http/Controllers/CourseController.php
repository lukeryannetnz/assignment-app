<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Nette\ArgumentOutOfRangeException;

class CourseController extends Controller
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
        $courses = Course::withCount('users')->paginate(4);

        // Get IDs of courses the user is enrolled in
        $enrolledCourseIds = $user->courses()->pluck('courses.id')->toArray();

        return view('courses.index', [
            'courses' => $courses,
            'enrolledCourseIds' => $enrolledCourseIds,
        ]);
    }

    /**
     * Display the specified course details.
     */
    public function show(Request $request, int $id): View
    {
        $course = Course::withCount('users')->findOrFail($id);
        $user = $request->user();
        if ($user == null) {
            throw new ArgumentOutOfRangeException("User is required.");
        }

        $isEnrolled = $user->courses()->where('courses.id', $id)->exists();

        return view('courses.show', [
            'course' => $course,
            'isEnrolled' => $isEnrolled,
        ]);
    }
}
