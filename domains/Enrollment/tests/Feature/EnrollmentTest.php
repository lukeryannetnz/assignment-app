<?php

declare(strict_types=1);

namespace Tests\Domains\Enrollment\Feature;

use App\Domains\IdentityAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Domains\Foundation\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function testStudentCanEnrollInCourse(): void
    {
        $tenantId = $this->insertTenantRecord('Enroll Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'enroll@example.test');
        $courseId = $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');

        $response = $this->actingAs($student)->post("/courses/{$courseId}/enroll");

        $response->assertRedirect();
        $this->assertTrue($this->enrollmentExists((int) $student->id, $courseId));
    }

    public function testEnrollmentRequiresAuthentication(): void
    {
        $tenantId = $this->insertTenantRecord('Guest Enrollment Tenant');
        $courseId = $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');

        $response = $this->post("/courses/{$courseId}/enroll");

        $response->assertRedirect('/login');
    }

    public function testStudentCannotEnrollInSameCourseTwice(): void
    {
        $tenantId = $this->insertTenantRecord('Duplicate Enrollment Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'duplicate@example.test');
        $courseId = $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');

        $this->actingAs($student)->post("/courses/{$courseId}/enroll");

        $response = $this->actingAs($student)->post("/courses/{$courseId}/enroll");

        $response->assertRedirect();
        $response->assertSessionHas('info');

        $this->assertSame(1, $this->enrollmentCount((int) $student->id, $courseId));
    }

    public function testStudentCanUnenrollFromCourse(): void
    {
        $tenantId = $this->insertTenantRecord('Unenroll Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'unenroll@example.test');
        $courseId = $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');

        $this->insertEnrollment($tenantId, (int) $student->id, $courseId);

        $response = $this->actingAs($student)->delete("/courses/{$courseId}/unenroll");

        $response->assertRedirect();
        $this->assertFalse($this->enrollmentExists((int) $student->id, $courseId));
    }

    public function testUnenrollmentRequiresAuthentication(): void
    {
        $tenantId = $this->insertTenantRecord('Guest Unenroll Tenant');
        $courseId = $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');

        $response = $this->delete("/courses/{$courseId}/unenroll");

        $response->assertRedirect('/login');
    }

    public function testEnrollmentSuccessMessageDisplayed(): void
    {
        $tenantId = $this->insertTenantRecord('Success Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'success@example.test');
        $courseId = $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');

        $response = $this->actingAs($student)->post("/courses/{$courseId}/enroll");

        $response->assertSessionHas('success');
    }

    public function testUnenrollmentSuccessMessageDisplayed(): void
    {
        $tenantId = $this->insertTenantRecord('Unenroll Success Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'unenroll-success@example.test');
        $courseId = $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');

        $this->insertEnrollment($tenantId, (int) $student->id, $courseId);

        $response = $this->actingAs($student)->delete("/courses/{$courseId}/unenroll");

        $response->assertSessionHas('success');
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

    private function insertEnrollment(int $tenantId, int $userId, int $courseId): void
    {
        DB::insert(
            'INSERT INTO course_user (tenant_id, user_id, course_id, enrolled_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$tenantId, $userId, $courseId, now(), now(), now()],
        );
    }

    private function enrollmentExists(int $userId, int $courseId): bool
    {
        $row = DB::selectOne(
            'SELECT 1 AS present
             FROM course_user
             WHERE user_id = ?
               AND course_id = ?
             LIMIT 1',
            [$userId, $courseId],
        );

        return $row !== null;
    }

    private function enrollmentCount(int $userId, int $courseId): int
    {
        /** @var object{aggregate: int|string}|null $row */
        $row = DB::selectOne(
            'SELECT COUNT(*) AS aggregate
             FROM course_user
             WHERE user_id = ?
               AND course_id = ?',
            [$userId, $courseId],
        );

        return $row !== null ? (int) $row->aggregate : 0;
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
