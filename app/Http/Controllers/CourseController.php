<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        $courses = Course::paginate(3);
        return view('course/index', ['courses' => $courses]);
    }

    public function create(): View
    {
        throw new \Exception(message: 'not implemented yet');
    }

    public function store(Request $request): RedirectResponse
    {
        throw new \Exception(message: 'not implemented yet' . $request->fullUrl());
    }

    public function show(int $id): View
    {
        throw new \Exception(message: 'not implemented yet' . $id);
    }
}
