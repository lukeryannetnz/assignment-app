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
}
