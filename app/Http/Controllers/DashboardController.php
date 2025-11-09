<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Nette\ArgumentOutOfRangeException;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        if ($user == null) {
            throw new ArgumentOutOfRangeException("User is required.");
        }

        $enrolledCourses = $user->courses()
            ->withCount('users')
            ->orderBy('course_user.created_at', 'desc')
            ->get();

        $popularCourses = collect();

        // If no enrolled courses, get top 3 most popular courses
        if ($enrolledCourses->isEmpty()) {
            $popularCourses = Course::withCount('users')
                ->orderBy('users_count', 'desc')
                ->limit(3)
                ->get();
        }

        return view('dashboard', [
            'enrolledCourses' => $enrolledCourses,
            'popularCourses' => $popularCourses,
        ]);
    }
}
