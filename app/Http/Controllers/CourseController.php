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
        $courses = Course::paginate(4);
        return view('course/index', ['courses' => $courses]);
    }

    public function create(): View
    {
        return view('course/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        Course::create($validated);

        return redirect()->route('courses')->with('success', 'Course created successfully!');
    }

    public function show(int $id): View
    {
        throw new \Exception(message: 'not implemented yet' . $id);
    }
}
