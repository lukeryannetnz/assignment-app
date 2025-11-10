<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $popularCourses = [];

        // If no enrolled courses, get top 3 most popular courses
        if ($enrolledCourses->isEmpty()) {
            $popularCourses = DB::select('
                SELECT
                    c.id,
                    c.name,
                    c.description,
                    COUNT(cu.user_id) as users_count
                FROM courses c
                LEFT JOIN course_user cu ON c.id = cu.course_id
                GROUP BY c.id
                ORDER BY users_count DESC
                LIMIT ?
            ', [3]);
        }

        return view('dashboard', [
            'enrolledCourses' => $enrolledCourses,
            'popularCourses' => $popularCourses,
        ]);
    }
}
