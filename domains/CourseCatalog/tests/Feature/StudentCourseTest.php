<?php

declare(strict_types=1);

namespace Tests\Domains\CourseCatalog\Feature;

use App\Domains\IdentityAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Domains\Foundation\TestCase;

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
        $tenantId = $this->insertTenantRecord('Student Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'student@example.test');
        $this->insertCourseRecord(
            $tenantId,
            'Learn PHP',
            'This course teaches you PHP fundamentals and best practices',
        );

        $response = $this->actingAs($student)->get('/courses');

        $response->assertOk();
        $response->assertViewHas('courses');
        $response->assertSee('Learn PHP');
    }

    public function testTheCoursesPageWithEmptyData(): void
    {
        $tenantId = $this->insertTenantRecord('Empty Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'empty@example.test');

        $response = $this->actingAs($student)->get('/courses');

        $response->assertOk();
        $response->assertSee('No courses');
    }

    public function testCourseShowPageRequiresAuthentication(): void
    {
        $tenantId = $this->insertTenantRecord('Guest Tenant');
        $courseId = $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');

        $response = $this->get("/courses/{$courseId}");

        $response->assertRedirect('/login');
    }

    public function testCourseShowPageDisplaysCourseDetails(): void
    {
        $tenantId = $this->insertTenantRecord('Show Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'show@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

        $response = $this->actingAs($student)->get("/courses/{$course->id}");

        $response->assertOk();
        $response->assertSee($course->name);
        $response->assertSee($course->description);
    }

    public function testCourseShowPageReturns404ForNonexistentCourse(): void
    {
        $tenantId = $this->insertTenantRecord('Not Found Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'missing@example.test');

        $response = $this->actingAs($student)->get('/courses/99999');

        $response->assertNotFound();
    }

    public function testCourseListShowsEnrolledStatus(): void
    {
        $tenantId = $this->insertTenantRecord('Enrolled Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'enrolled@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();
        $this->enrollStudentInCourse((int) $student->id, (int) $student->tenant_id, (int) $course->id);

        $response = $this->actingAs($student)->get('/courses');

        $response->assertOk();
        $response->assertSee('Enrolled');
    }

    public function testCourseListShowsEnrollButtonForUnenrolledCourses(): void
    {
        $tenantId = $this->insertTenantRecord('Browse Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'browse@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');

        $response = $this->actingAs($student)->get('/courses');

        $response->assertOk();
        $response->assertSee('Enroll');
    }

    public function testCourseShowPageShowsEnrolledStatusWhenEnrolled(): void
    {
        $tenantId = $this->insertTenantRecord('Enrolled Show Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'show-enrolled@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();
        $this->enrollStudentInCourse((int) $student->id, (int) $student->tenant_id, (int) $course->id);

        $response = $this->actingAs($student)->get("/courses/{$course->id}");

        $response->assertOk();
        $response->assertSee('You are enrolled');
    }

    public function testCourseShowPageShowsEnrollButtonWhenNotEnrolled(): void
    {
        $tenantId = $this->insertTenantRecord('Unenrolled Show Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'show-unenrolled@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

        $response = $this->actingAs($student)->get("/courses/{$course->id}");

        $response->assertOk();
        $response->assertSee('Enroll in This Course');
    }

    public function testCourseShowPageDisplaysEnrollmentCount(): void
    {
        $tenantId = $this->insertTenantRecord('Count Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'count@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

        for ($index = 1; $index <= 5; $index++) {
            $enrolledStudent = $this->createUserRecord(
                $tenantId,
                false,
                true,
                "enrolled-{$index}@example.test",
            );
            $this->enrollStudentInCourse((int) $enrolledStudent->id, $tenantId, (int) $course->id);
        }

        $response = $this->actingAs($student)->get("/courses/{$course->id}");

        $response->assertOk();
        $response->assertSee('5 students');
    }

    public function testDashboardShowsEnrolledCoursesForEnrolledStudent(): void
    {
        $tenantId = $this->insertTenantRecord('Dashboard Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'dashboard@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

        $this->enrollStudentInCourse((int) $student->id, (int) $student->tenant_id, (int) $course->id);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('My Enrolled Courses');
        $response->assertSee($course->name);
    }

    public function testDashboardShowsPopularCoursesForUnenrolledStudent(): void
    {
        $tenantId = $this->insertTenantRecord('Popular Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'popular@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');

        $firstCourse = $this->firstCourse();
        for ($index = 1; $index <= 3; $index++) {
            $otherStudent = $this->createUserRecord(
                $tenantId,
                false,
                true,
                "popular-student-{$index}@example.test",
            );
            $this->enrollStudentInCourse((int) $otherStudent->id, $tenantId, (int) $firstCourse->id);
        }

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Top 3 Most Popular Courses');
    }

    /**
     * @return object{id: int, tenant_id: int, name: string, description: string}
     */
    private function firstCourse(): object
    {
        /** @var object{id: int, tenant_id: int, name: string, description: string}|null $course */
        $course = DB::selectOne(
            'SELECT id, tenant_id, name, description
             FROM courses
             ORDER BY id ASC
             LIMIT 1',
        );

        $this->assertNotNull($course);

        return (object) [
            'id' => (int) $course->id,
            'tenant_id' => (int) $course->tenant_id,
            'name' => (string) $course->name,
            'description' => (string) $course->description,
        ];
    }

    private function insertTenantRecord(string $name): int
    {
        DB::insert(
            'INSERT INTO tenants (name, status, plan_tier, hierarchy_depth_limit, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$name, 'active', 'enterprise_pilot', 4, now(), now()],
        );

        return $this->lastInsertId();
    }

    private function createUserRecord(int $tenantId, bool $isAdmin, bool $isStudent, string $email): User
    {
        $name = $isAdmin ? 'Admin User' : 'Student User';
        $password = bcrypt('password');
        $rememberToken = substr(md5($email), 0, 10);

        DB::insert(
            'INSERT INTO users
                (tenant_id, name, email, email_verified_at, password, remember_token,
                 is_student, is_admin, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $tenantId,
                $name,
                $email,
                now(),
                $password,
                $rememberToken,
                $isStudent,
                $isAdmin,
                now(),
                now(),
            ],
        );

        return $this->makeUser(
            $this->lastInsertId(),
            $tenantId,
            $name,
            $email,
            $password,
            $rememberToken,
            $isStudent,
            $isAdmin,
        );
    }

    private function insertCourseRecord(int $tenantId, string $name, string $description): int
    {
        DB::insert(
            'INSERT INTO courses (tenant_id, name, description, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?)',
            [$tenantId, $name, $description, now(), now()],
        );

        return $this->lastInsertId();
    }

    private function enrollStudentInCourse(int $userId, int $tenantId, int $courseId): void
    {
        DB::insert(
            'INSERT INTO course_user (tenant_id, user_id, course_id, enrolled_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$tenantId, $userId, $courseId, now(), now(), now()],
        );
    }

    private function makeUser(
        int $id,
        int $tenantId,
        string $name,
        string $email,
        string $password,
        string $rememberToken,
        bool $isStudent,
        bool $isAdmin,
    ): User {
        $user = new User();
        $user->forceFill([
            'id' => $id,
            'tenant_id' => $tenantId,
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => $password,
            'remember_token' => $rememberToken,
            'is_student' => $isStudent,
            'is_admin' => $isAdmin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user->exists = true;

        return $user;
    }

    private function lastInsertId(): int
    {
        return (int) DB::getPdo()->lastInsertId();
    }
}
