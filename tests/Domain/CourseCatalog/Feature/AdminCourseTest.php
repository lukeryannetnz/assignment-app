<?php

declare(strict_types=1);

namespace Tests\Domain\CourseCatalog\Feature;

use App\Domain\IdentityAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Foundation\TestCase;

class AdminCourseTest extends TestCase
{
    use RefreshDatabase;

    public function testTheCourseCreatePageRequiresAuthentication(): void
    {
        $response = $this->get('/admin/course-catalog/courses/create');

        $response->assertRedirect('/login');
    }

    public function testTheCourseCreatePageRequiresAdminRole(): void
    {
        $student = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($student)->get('/admin/course-catalog/courses/create');

        $response->assertStatus(403);
    }

    public function testTheCourseCreatePageReturnsASuccessfulResult(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get('/admin/course-catalog/courses/create');

        $response->assertOk();
        $response->assertSee('Create New Course');
        $response->assertSee('Course Name');
        $response->assertSee('Course Description');
    }

    public function testCanCreateACourseWithValidData(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $courseData = [
            'name' => 'Test Course',
            'description' => 'This is a test course description',
        ];

        $response = $this->actingAs($admin)->post('/admin/course-catalog/courses', $courseData);

        $response->assertRedirect('/admin/course-catalog/courses');
        $this->assertDatabaseHas('courses', $courseData);
    }

    public function testCannotCreateACourseWithoutName(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $courseData = [
            'description' => 'This is a test course description',
        ];

        $response = $this->actingAs($admin)->post('/admin/course-catalog/courses', $courseData);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('courses', $courseData);
    }

    public function testCannotCreateACourseWithoutDescription(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $courseData = [
            'name' => 'Test Course',
        ];

        $response = $this->actingAs($admin)->post('/admin/course-catalog/courses', $courseData);

        $response->assertSessionHasErrors('description');
        $this->assertDatabaseMissing('courses', $courseData);
    }

    public function testNewCourseAppearsInCourseList(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $courseData = [
            'name' => 'New Test Course',
            'description' => 'This course should appear in the list',
        ];

        $this->actingAs($admin)->post('/admin/course-catalog/courses', $courseData);

        $response = $this->actingAs($admin)->get('/admin/course-catalog/courses');

        $response->assertOk();
        $response->assertSee('New Test Course');
        $response->assertSee('This course should appear in the list');
    }

    public function testTheCourseEditPageRequiresAuthentication(): void
    {
        $this->seed();
        $course = \App\Domain\CourseCatalog\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->get("/admin/course-catalog/courses/{$course->id}/edit");

        $response->assertRedirect('/login');
    }

    public function testTheCourseEditPageRequiresAdminRole(): void
    {
        $this->seed();
        $student = User::factory()->create(['is_admin' => false]);
        $course = \App\Domain\CourseCatalog\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->actingAs($student)->get("/admin/course-catalog/courses/{$course->id}/edit");

        $response->assertStatus(403);
    }

    public function testTheCourseEditPageReturnsASuccessfulResult(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->seed();
        $course = \App\Domain\CourseCatalog\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->actingAs($admin)->get("/admin/course-catalog/courses/{$course->id}/edit");

        $response->assertOk();
        $response->assertSee('Edit Course');
        $response->assertSee('Course Name');
        $response->assertSee('Course Description');
        $response->assertSee($course->name);
        $response->assertSee($course->description);
    }

    public function testTheCourseEditPageReturns404ForNonexistentCourse(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get('/admin/course-catalog/courses/99999/edit');

        $response->assertNotFound();
    }

    public function testCanUpdateACourseWithValidData(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->seed();
        $course = \App\Domain\CourseCatalog\Models\Course::first();
        $this->assertNotNull($course);

        $updatedData = [
            'name' => 'Updated Course Name',
            'description' => 'Updated course description',
        ];

        $response = $this->actingAs($admin)->put("/admin/course-catalog/courses/{$course->id}", $updatedData);

        $response->assertRedirect('/admin/course-catalog/courses?page=1');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Updated Course Name',
            'description' => 'Updated course description',
        ]);
    }

    public function testCannotUpdateACourseWithoutName(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->seed();
        $course = \App\Domain\CourseCatalog\Models\Course::first();
        $this->assertNotNull($course);

        $updatedData = [
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($admin)->put("/admin/course-catalog/courses/{$course->id}", $updatedData);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => $course->name,
        ]);
    }

    public function testCannotUpdateACourseWithoutDescription(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->seed();
        $course = \App\Domain\CourseCatalog\Models\Course::first();
        $this->assertNotNull($course);

        $updatedData = [
            'name' => 'Updated Name',
        ];

        $response = $this->actingAs($admin)->put("/admin/course-catalog/courses/{$course->id}", $updatedData);

        $response->assertSessionHasErrors('description');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'description' => $course->description,
        ]);
    }

    public function testUpdateCourseReturns404ForNonexistentCourse(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $updatedData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($admin)->put('/admin/course-catalog/courses/99999', $updatedData);

        $response->assertNotFound();
    }

    public function testCourseListContainsEditLinks(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->seed();
        $course = \App\Domain\CourseCatalog\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->actingAs($admin)->get('/admin/course-catalog/courses');

        $response->assertOk();
        $response->assertSee("/admin/course-catalog/courses/{$course->id}/edit");
    }

    public function testEditFormDisplaysCurrentCourseValues(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $courseData = [
            'name' => 'Specific Test Course',
            'description' => 'Specific test description',
        ];

        $this->actingAs($admin)->post('/admin/course-catalog/courses', $courseData);
        $course = \App\Domain\CourseCatalog\Models\Course::where('name', 'Specific Test Course')->first();
        $this->assertNotNull($course);

        $response = $this->actingAs($admin)->get("/admin/course-catalog/courses/{$course->id}/edit");

        $response->assertOk();
        $response->assertSee('value="Specific Test Course"', false);
        $response->assertSee('Specific test description');
    }

    public function testUpdatedCourseAppearsInCourseList(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->seed();
        $course = \App\Domain\CourseCatalog\Models\Course::first();
        $this->assertNotNull($course);

        $updatedData = [
            'name' => 'Completely New Course Name',
            'description' => 'Completely new description',
        ];

        $this->actingAs($admin)->put("/admin/course-catalog/courses/{$course->id}", $updatedData);

        $response = $this->actingAs($admin)->get('/admin/course-catalog/courses');

        $response->assertOk();
        $response->assertSee('Completely New Course Name');
        $response->assertSee('Completely new description');
    }

    public function testCanCancelEditAndReturnToCourseList(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->seed();
        $course = \App\Domain\CourseCatalog\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->actingAs($admin)->get("/admin/course-catalog/courses/{$course->id}/edit");

        $response->assertOk();
        $response->assertSee('Cancel');
        $response->assertSee('href="' . route('course-catalog.admin.courses.index', ['page' => '1']) . '"', false);
    }

    public function testEditPagePreservesPageParameter(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->seed();
        $course = \App\Domain\CourseCatalog\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->actingAs($admin)->get("/admin/course-catalog/courses/{$course->id}/edit?page=2");

        $response->assertOk();
        $response->assertSee('value="2"', false);
    }

    public function testUpdateRedirectsToCorrectPage(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->seed();
        $course = \App\Domain\CourseCatalog\Models\Course::first();
        $this->assertNotNull($course);

        $updatedData = [
            'name' => 'Updated Name for Page Test',
            'description' => 'Updated description for page test',
            'page' => '3',
        ];

        $response = $this->actingAs($admin)->put("/admin/course-catalog/courses/{$course->id}", $updatedData);

        $response->assertRedirect('/admin/course-catalog/courses?page=3');
    }

    public function testCancelButtonPreservesPageParameter(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->seed();
        $course = \App\Domain\CourseCatalog\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->actingAs($admin)->get("/admin/course-catalog/courses/{$course->id}/edit?page=2");

        $response->assertOk();
        $response->assertSee('href="' . route('course-catalog.admin.courses.index', ['page' => '2']) . '"', false);
    }

    public function testUpdateDefaultsToPage1WhenPageNotProvided(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->seed();
        $course = \App\Domain\CourseCatalog\Models\Course::first();
        $this->assertNotNull($course);

        $updatedData = [
            'name' => 'Updated Name Without Page',
            'description' => 'Updated description without page',
        ];

        $response = $this->actingAs($admin)->put("/admin/course-catalog/courses/{$course->id}", $updatedData);

        $response->assertRedirect('/admin/course-catalog/courses?page=1');
    }

    public function testCanDeleteACourse(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->seed();
        $course = \App\Domain\CourseCatalog\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->actingAs($admin)->delete("/admin/course-catalog/courses/{$course->id}");

        $response->assertRedirect('/admin/course-catalog/courses');
        $this->assertDatabaseMissing('courses', [
            'id' => $course->id,
        ]);
    }

    public function testStudentCannotAccessAdminCourseManagement(): void
    {
        $student = User::factory()->create(['is_admin' => false, 'is_student' => true]);

        $response = $this->actingAs($student)->get('/admin/course-catalog/courses');
        $response->assertStatus(403);

        $response = $this->actingAs($student)->get('/admin/course-catalog/courses/create');
        $response->assertStatus(403);
    }

    public function testStudentCannotCreateCourse(): void
    {
        $student = User::factory()->create(['is_admin' => false, 'is_student' => true]);
        $courseData = [
            'name' => 'Test Course',
            'description' => 'This is a test course description',
        ];

        $response = $this->actingAs($student)->post('/admin/course-catalog/courses', $courseData);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('courses', $courseData);
    }

    public function testStudentCannotUpdateCourse(): void
    {
        $student = User::factory()->create(['is_admin' => false, 'is_student' => true]);
        $this->seed();
        $course = \App\Domain\CourseCatalog\Models\Course::first();
        $this->assertNotNull($course);

        $updatedData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($student)->put("/admin/course-catalog/courses/{$course->id}", $updatedData);

        $response->assertStatus(403);
    }

    public function testStudentCannotDeleteCourse(): void
    {
        $student = User::factory()->create(['is_admin' => false, 'is_student' => true]);
        $this->seed();
        $course = \App\Domain\CourseCatalog\Models\Course::first();
        $this->assertNotNull($course);

        $response = $this->actingAs($student)->delete("/admin/course-catalog/courses/{$course->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
        ]);
    }
}
