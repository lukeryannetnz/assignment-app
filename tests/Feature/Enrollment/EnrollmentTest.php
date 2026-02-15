<?php

declare(strict_types=1);

namespace Tests\Feature\Enrollment;

use App\Models\CourseCatalog\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function testStudentCanEnrollInCourse(): void
    {
        $student = User::factory()->create();
        $this->seed();
        $course = Course::first();
        $this->assertNotNull($course);

        $response = $this->actingAs($student)->post("/courses/{$course->id}/enroll");

        $response->assertRedirect();
        $this->assertDatabaseHas('course_user', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    public function testEnrollmentRequiresAuthentication(): void
    {
        $this->seed();
        $course = Course::first();
        $this->assertNotNull($course);

        $response = $this->post("/courses/{$course->id}/enroll");

        $response->assertRedirect('/login');
    }

    public function testStudentCannotEnrollInSameCourseTwice(): void
    {
        $student = User::factory()->create();
        $this->seed();
        $course = Course::first();
        $this->assertNotNull($course);

        $this->actingAs($student)->post("/courses/{$course->id}/enroll");

        $response = $this->actingAs($student)->post("/courses/{$course->id}/enroll");

        $response->assertRedirect();
        $response->assertSessionHas('info');

        $enrollmentCount = DB::table('course_user')
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->count();

        $this->assertEquals(1, $enrollmentCount);
    }

    public function testStudentCanUnenrollFromCourse(): void
    {
        $student = User::factory()->create();
        $this->seed();
        $course = Course::first();
        $this->assertNotNull($course);

        $student->courses()->attach($course->id);

        $response = $this->actingAs($student)->delete("/courses/{$course->id}/unenroll");

        $response->assertRedirect();
        $this->assertDatabaseMissing('course_user', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    public function testUnenrollmentRequiresAuthentication(): void
    {
        $this->seed();
        $course = Course::first();
        $this->assertNotNull($course);

        $response = $this->delete("/courses/{$course->id}/unenroll");

        $response->assertRedirect('/login');
    }

    public function testEnrollmentSuccessMessageDisplayed(): void
    {
        $student = User::factory()->create();
        $this->seed();
        $course = Course::first();
        $this->assertNotNull($course);

        $response = $this->actingAs($student)->post("/courses/{$course->id}/enroll");

        $response->assertSessionHas('success');
    }

    public function testUnenrollmentSuccessMessageDisplayed(): void
    {
        $student = User::factory()->create();
        $this->seed();
        $course = Course::first();
        $this->assertNotNull($course);

        $student->courses()->attach($course->id);

        $response = $this->actingAs($student)->delete("/courses/{$course->id}/unenroll");

        $response->assertSessionHas('success');
    }
}
