<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    public function testTheCoursesPageRequiresAuthentication(): void
    {
        $response = $this->get('/courses');

        $response->assertRedirect('/login');
    }

    public function testTheCoursesPageReturnsASuccessfulResult(): void
    {
        $user = User::factory()->create();
        $this->seed();

        $response = $this->actingAs($user)->get('/courses');

        $response->assertOk();
        $response->assertViewHas('courses');
        $response->assertSee('Learn PHP');
    }

    public function testTheCoursesPageWithEmptyData(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/courses');

        $response->assertOk();
        $response->assertSee('No courses');
    }

    public function testTheCourseCreatePageRequiresAuthentication(): void
    {
        $response = $this->get('/courses/create');

        $response->assertRedirect('/login');
    }

    public function testTheCourseCreatePageReturnsASuccessfulResult(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/courses/create');

        $response->assertOk();
        $response->assertSee('Create New Course');
        $response->assertSee('Course Name');
        $response->assertSee('Course Description');
    }

    public function testCanCreateACourseWithValidData(): void
    {
        $user = User::factory()->create();
        $courseData = [
            'name' => 'Test Course',
            'description' => 'This is a test course description',
        ];

        $response = $this->actingAs($user)->post('/courses', $courseData);

        $response->assertRedirect('/courses');
        $this->assertDatabaseHas('courses', $courseData);
    }

    public function testCannotCreateACourseWithoutName(): void
    {
        $user = User::factory()->create();
        $courseData = [
            'description' => 'This is a test course description',
        ];

        $response = $this->actingAs($user)->post('/courses', $courseData);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('courses', $courseData);
    }

    public function testCannotCreateACourseWithoutDescription(): void
    {
        $user = User::factory()->create();
        $courseData = [
            'name' => 'Test Course',
        ];

        $response = $this->actingAs($user)->post('/courses', $courseData);

        $response->assertSessionHasErrors('description');
        $this->assertDatabaseMissing('courses', $courseData);
    }

    public function testNewCourseAppearsInCourseList(): void
    {
        $user = User::factory()->create();
        $courseData = [
            'name' => 'New Test Course',
            'description' => 'This course should appear in the list',
        ];

        $this->actingAs($user)->post('/courses', $courseData);

        $response = $this->actingAs($user)->get('/courses');

        $response->assertOk();
        $response->assertSee('New Test Course');
        $response->assertSee('This course should appear in the list');
    }

    public function testTheCourseEditPageRequiresAuthentication(): void
    {
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->get("/courses/{$course->id}/edit");

        $response->assertRedirect('/login');
    }

    public function testTheCourseEditPageReturnsASuccessfulResult(): void
    {
        $user = User::factory()->create();
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->actingAs($user)->get("/courses/{$course->id}/edit");

        $response->assertOk();
        $response->assertSee('Edit Course');
        $response->assertSee('Course Name');
        $response->assertSee('Course Description');
        $response->assertSee($course->name);
        $response->assertSee($course->description);
    }

    public function testTheCourseEditPageReturns404ForNonexistentCourse(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/courses/99999/edit');

        $response->assertNotFound();
    }

    public function testCanUpdateACourseWithValidData(): void
    {
        $user = User::factory()->create();
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $updatedData = [
            'name' => 'Updated Course Name',
            'description' => 'Updated course description',
        ];

        $response = $this->actingAs($user)->put("/courses/{$course->id}", $updatedData);

        $response->assertRedirect('/courses?page=1');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Updated Course Name',
            'description' => 'Updated course description',
        ]);
    }

    public function testCannotUpdateACourseWithoutName(): void
    {
        $user = User::factory()->create();
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $updatedData = [
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($user)->put("/courses/{$course->id}", $updatedData);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => $course->name,
        ]);
    }

    public function testCannotUpdateACourseWithoutDescription(): void
    {
        $user = User::factory()->create();
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $updatedData = [
            'name' => 'Updated Name',
        ];

        $response = $this->actingAs($user)->put("/courses/{$course->id}", $updatedData);

        $response->assertSessionHasErrors('description');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'description' => $course->description,
        ]);
    }

    public function testUpdateCourseReturns404ForNonexistentCourse(): void
    {
        $user = User::factory()->create();
        $updatedData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($user)->put('/courses/99999', $updatedData);

        $response->assertNotFound();
    }

    public function testCourseListContainsEditLinks(): void
    {
        $user = User::factory()->create();
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->actingAs($user)->get('/courses');

        $response->assertOk();
        $response->assertSee("/courses/{$course->id}/edit");
    }

    public function testEditFormDisplaysCurrentCourseValues(): void
    {
        $user = User::factory()->create();
        $courseData = [
            'name' => 'Specific Test Course',
            'description' => 'Specific test description',
        ];

        $this->actingAs($user)->post('/courses', $courseData);
        $course = \App\Models\Course::where('name', 'Specific Test Course')->first();
        $this->assertNotNull($course);

        $response = $this->actingAs($user)->get("/courses/{$course->id}/edit");

        $response->assertOk();
        $response->assertSee('value="Specific Test Course"', false);
        $response->assertSee('Specific test description');
    }

    public function testUpdatedCourseAppearsInCourseList(): void
    {
        $user = User::factory()->create();
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $updatedData = [
            'name' => 'Completely New Course Name',
            'description' => 'Completely new description',
        ];

        $this->actingAs($user)->put("/courses/{$course->id}", $updatedData);

        $response = $this->actingAs($user)->get('/courses');

        $response->assertOk();
        $response->assertSee('Completely New Course Name');
        $response->assertSee('Completely new description');
    }

    public function testCanCancelEditAndReturnToCourseList(): void
    {
        $user = User::factory()->create();
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->actingAs($user)->get("/courses/{$course->id}/edit");

        $response->assertOk();
        $response->assertSee('Cancel');
        $response->assertSee('href="' . route('courses', ['page' => '1']) . '"', false);
    }

    public function testEditPagePreservesPageParameter(): void
    {
        $user = User::factory()->create();
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->actingAs($user)->get("/courses/{$course->id}/edit?page=2");

        $response->assertOk();
        $response->assertSee('value="2"', false);
    }

    public function testUpdateRedirectsToCorrectPage(): void
    {
        $user = User::factory()->create();
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $updatedData = [
            'name' => 'Updated Name for Page Test',
            'description' => 'Updated description for page test',
            'page' => '3',
        ];

        $response = $this->actingAs($user)->put("/courses/{$course->id}", $updatedData);

        $response->assertRedirect('/courses?page=3');
    }

    public function testCancelButtonPreservesPageParameter(): void
    {
        $user = User::factory()->create();
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->actingAs($user)->get("/courses/{$course->id}/edit?page=2");

        $response->assertOk();
        $response->assertSee('href="' . route('courses', ['page' => '2']) . '"', false);
    }

    public function testUpdateDefaultsToPage1WhenPageNotProvided(): void
    {
        $user = User::factory()->create();
        $this->seed();
        $course = \App\Models\Course::first();
        $this->assertNotNull($course);

        $updatedData = [
            'name' => 'Updated Name Without Page',
            'description' => 'Updated description without page',
        ];

        $response = $this->actingAs($user)->put("/courses/{$course->id}", $updatedData);

        $response->assertRedirect('/courses?page=1');
    }
}
