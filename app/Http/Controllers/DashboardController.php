<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Nette\ArgumentOutOfRangeException;

class DashboardController
{
    public function index(Request $request): View
    {
        $user = $request->user();
        if ($user == null) {
            throw new ArgumentOutOfRangeException("User is required.");
        }

        $enrolledCourses = DB::select('
            SELECT
                c.id,
                c.name,
                c.description,
                COUNT(allCourseUsers.user_id) as users_count
            FROM courses c
            INNER JOIN course_user cu ON c.id = cu.course_id
                AND cu.user_id = ?
            LEFT JOIN course_user allCourseUsers ON c.id = allCourseUsers.course_id
            GROUP BY c.id, c.name, c.description
            ORDER BY cu.created_at DESC
        ', [$user->id]);

        $popularCourses = [];

        // If no enrolled courses, get top 3 most popular courses
        if (empty($enrolledCourses)) {
            $popularCourses = DB::select('
                SELECT
                    c.id,
                    c.name,
                    c.description,
                    COUNT(cu.user_id) as users_count
                FROM courses c
                LEFT JOIN course_user cu ON c.id = cu.course_id
                GROUP BY c.id, c.name, c.description
                ORDER BY users_count DESC
                LIMIT ?
            ', [3]) ;
        }

        return view('dashboard', [
            'enrolledCourses' => $enrolledCourses,
            'popularCourses' => $popularCourses,
        ]);
    }
}
