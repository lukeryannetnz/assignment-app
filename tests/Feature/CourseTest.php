<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    public function testTheCoursesPageReturnsASuccessfulResult(): void
    {
        $this->seed();
        $response = $this->get('/courses');

        $response->assertOk();
        $response->assertViewHas('courses');
        $response->assertSee('Learn PHP');
    }

    public function testTheCoursesPageWithEmptyData(): void
    {
        $response = $this->get('/courses');

        $response->assertOk();
        $response->assertSee('No courses');
    }

    public function testTheCourseCreatePageReturnsASuccessfulResult(): void
    {
        $response = $this->get('/courses/create');

        $response->assertOk();
        $response->assertSee('Create New Course');
        $response->assertSee('Course Name');
        $response->assertSee('Course Description');
    }

    public function testCanCreateACourseWithValidData(): void
    {
        $courseData = [
            'name' => 'Test Course',
            'description' => 'This is a test course description',
        ];

        $response = $this->post('/courses', $courseData);

        $response->assertRedirect('/courses');
        $this->assertDatabaseHas('courses', $courseData);
    }

    public function testCannotCreateACourseWithoutName(): void
    {
        $courseData = [
            'description' => 'This is a test course description',
        ];

        $response = $this->post('/courses', $courseData);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('courses', $courseData);
    }

    public function testCannotCreateACourseWithoutDescription(): void
    {
        $courseData = [
            'name' => 'Test Course',
        ];

        $response = $this->post('/courses', $courseData);

        $response->assertSessionHasErrors('description');
        $this->assertDatabaseMissing('courses', $courseData);
    }

    public function testNewCourseAppearsInCourseList(): void
    {
        $courseData = [
            'name' => 'New Test Course',
            'description' => 'This course should appear in the list',
        ];

        $this->post('/courses', $courseData);

        $response = $this->get('/courses');

        $response->assertOk();
        $response->assertSee('New Test Course');
        $response->assertSee('This course should appear in the list');
    }

    public function testTheCourseEditPageReturnsASuccessfulResult(): void
    {
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->get("/courses/{$course->id}/edit");

        $response->assertOk();
        $response->assertSee('Edit Course');
        $response->assertSee('Course Name');
        $response->assertSee('Course Description');
        $response->assertSee($course->name);
        $response->assertSee($course->description);
    }

    public function testTheCourseEditPageReturns404ForNonexistentCourse(): void
    {
        $response = $this->get('/courses/99999/edit');

        $response->assertNotFound();
    }

    public function testCanUpdateACourseWithValidData(): void
    {
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $updatedData = [
            'name' => 'Updated Course Name',
            'description' => 'Updated course description',
        ];

        $response = $this->put("/courses/{$course->id}", $updatedData);

        $response->assertRedirect('/courses');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Updated Course Name',
            'description' => 'Updated course description',
        ]);
    }

    public function testCannotUpdateACourseWithoutName(): void
    {
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $updatedData = [
            'description' => 'Updated description',
        ];

        $response = $this->put("/courses/{$course->id}", $updatedData);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => $course->name,
        ]);
    }

    public function testCannotUpdateACourseWithoutDescription(): void
    {
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $updatedData = [
            'name' => 'Updated Name',
        ];

        $response = $this->put("/courses/{$course->id}", $updatedData);

        $response->assertSessionHasErrors('description');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'description' => $course->description,
        ]);
    }

    public function testUpdateCourseReturns404ForNonexistentCourse(): void
    {
        $updatedData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $response = $this->put('/courses/99999', $updatedData);

        $response->assertNotFound();
    }

    public function testCourseListContainsEditLinks(): void
    {
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->get('/courses');

        $response->assertOk();
        $response->assertSee("/courses/{$course->id}/edit");
    }

    public function testEditFormDisplaysCurrentCourseValues(): void
    {
        $courseData = [
            'name' => 'Specific Test Course',
            'description' => 'Specific test description',
        ];

        $this->post('/courses', $courseData);
        $course = \App\Models\Course::where('name', 'Specific Test Course')->first();
        $this->assertNotNull($course);

        $response = $this->get("/courses/{$course->id}/edit");

        $response->assertOk();
        $response->assertSee('value="Specific Test Course"', false);
        $response->assertSee('Specific test description');
    }

    public function testUpdatedCourseAppearsInCourseList(): void
    {
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $updatedData = [
            'name' => 'Completely New Course Name',
            'description' => 'Completely new description',
        ];

        $this->put("/courses/{$course->id}", $updatedData);

        $response = $this->get('/courses');

        $response->assertOk();
        $response->assertSee('Completely New Course Name');
        $response->assertSee('Completely new description');
    }

    public function testCanCancelEditAndReturnToCourseList(): void
    {
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->get("/courses/{$course->id}/edit");

        $response->assertOk();
        $response->assertSee('Cancel');
        $response->assertSee('href="' . route('courses') . '"', false);
    }
}
