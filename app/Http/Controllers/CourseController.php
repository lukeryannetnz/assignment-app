<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Models\Course;

class CourseController extends Controller
{
    public function index(): View {
        $courses = Course::paginate(15);
        return view("courseall", ['courses' => $courses]);
    }
}
