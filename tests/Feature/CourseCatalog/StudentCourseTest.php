<?php

declare(strict_types=1);

namespace Tests\Feature\CourseCatalog;

use App\Models\CourseCatalog\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCourseTest extends TestCase
{
    use RefreshDatabase;

    public function testTheCoursesPageRequiresAuthentication(): void
    {
        $response = $this->get('/courses');

        $response->assertRedirect('/login');
    }

    public function testTheCoursesPageReturnsASuccessfulResult(): void
    {
        $student = User::factory()->create();
        $this->seed();

        $response = $this->actingAs($student)->get('/courses');

        $response->assertOk();
        $response->assertViewHas('courses');
        $response->assertSee('Learn PHP');
    }

    public function testTheCoursesPageWithEmptyData(): void
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)->get('/courses');

        $response->assertOk();
        $response->assertSee('No courses');
    }

    public function testCourseShowPageRequiresAuthentication(): void
    {
        $this->seed();
        $course = Course::first();
        $this->assertNotNull($course);

        $response = $this->get("/courses/{$course->id}");

        $response->assertRedirect('/login');
    }

    public function testCourseShowPageDisplaysCourseDetails(): void
    {
        $student = User::factory()->create();
        $this->seed();
        $course = Course::first();
        $this->assertNotNull($course);

        $response = $this->actingAs($student)->get("/courses/{$course->id}");

        $response->assertOk();
        $response->assertSee($course->name);
        $response->assertSee($course->description);
    }

    public function testCourseShowPageReturns404ForNonexistentCourse(): void
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)->get('/courses/99999');

        $response->assertNotFound();
    }

    public function testCourseListShowsEnrolledStatus(): void
    {
        $student = User::factory()->create();
        $this->seed();
        $course = Course::first();
        $this->assertNotNull($course);

        // Enroll in course
        $student->courses()->attach($course->id);

        $response = $this->actingAs($student)->get('/courses');

        $response->assertOk();
        $response->assertSee('Enrolled');
    }

    public function testCourseListShowsEnrollButtonForUnenrolledCourses(): void
    {
        $student = User::factory()->create();
        $this->seed();

        $response = $this->actingAs($student)->get('/courses');

        $response->assertOk();
        $response->assertSee('Enroll');
    }

    public function testCourseShowPageShowsEnrolledStatusWhenEnrolled(): void
    {
        $student = User::factory()->create();
        $this->seed();
        $course = Course::first();
        $this->assertNotNull($course);

        // Enroll in course
        $student->courses()->attach($course->id);

        $response = $this->actingAs($student)->get("/courses/{$course->id}");

        $response->assertOk();
        $response->assertSee('You are enrolled');
    }

    public function testCourseShowPageShowsEnrollButtonWhenNotEnrolled(): void
    {
        $student = User::factory()->create();
        $this->seed();
        $course = Course::first();
        $this->assertNotNull($course);

        $response = $this->actingAs($student)->get("/courses/{$course->id}");

        $response->assertOk();
        $response->assertSee('Enroll in This Course');
    }

    public function testCourseShowPageDisplaysEnrollmentCount(): void
    {
        $student = User::factory()->create();
        $this->seed();
        $course = Course::first();
        $this->assertNotNull($course);

        // Enroll some students
        $students = User::factory()->count(5)->create();
        foreach ($students as $s) {
            $s->courses()->attach($course->id);
        }

        $response = $this->actingAs($student)->get("/courses/{$course->id}");

        $response->assertOk();
        $response->assertSee('5 students');
    }

    public function testDashboardShowsEnrolledCoursesForEnrolledStudent(): void
    {
        $student = User::factory()->create();
        $this->seed();
        $course = Course::first();
        $this->assertNotNull($course);

        // Enroll in course
        $student->courses()->attach($course->id);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('My Enrolled Courses');
        $response->assertSee($course->name);
    }

    public function testDashboardShowsPopularCoursesForUnenrolledStudent(): void
    {
        $student = User::factory()->create();
        $this->seed();

        // Create some enrollments for other students to make courses popular
        $otherStudents = User::factory()->count(3)->create();
        $courses = Course::all();
        $firstCourse = $courses->first();
        $this->assertNotNull($firstCourse);
        foreach ($otherStudents as $s) {
            $s->courses()->attach($firstCourse->id);
        }

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Top 3 Most Popular Courses');
    }
}
