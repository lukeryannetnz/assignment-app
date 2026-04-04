<?php

declare(strict_types=1);

namespace Tests\Domains\CourseCatalog\Feature;

use App\Domains\IdentityAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Domains\Foundation\TestCase;

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
        $tenantId = $this->insertTenantRecord('Student Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'student-admin-page@example.test');

        $response = $this->actingAs($student)->get('/admin/course-catalog/courses/create');

        $response->assertStatus(403);
    }

    public function testTheCourseCreatePageReturnsASuccessfulResult(): void
    {
        $tenantId = $this->insertTenantRecord('Admin Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-create-page@example.test');

        $response = $this->actingAs($admin)->get('/admin/course-catalog/courses/create');

        $response->assertOk();
        $response->assertSee('Create New Course');
        $response->assertSee('Course Name');
        $response->assertSee('Course Description');
    }

    public function testCanCreateACourseWithValidData(): void
    {
        $tenantId = $this->insertTenantRecord('Create Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-create@example.test');
        $courseData = [
            'name' => 'Test Course',
            'description' => 'This is a test course description',
        ];

        $response = $this->actingAs($admin)->post('/admin/course-catalog/courses', $courseData);

        $response->assertRedirect('/admin/course-catalog/courses');
        $course = $this->findCourseByName('Test Course');
        $this->assertSame($tenantId, $course->tenant_id);
        $this->assertSame('This is a test course description', $course->description);
    }

    public function testCannotCreateACourseWithoutName(): void
    {
        $tenantId = $this->insertTenantRecord('Invalid Create Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-missing-name@example.test');
        $courseData = [
            'description' => 'This is a test course description',
        ];

        $response = $this->actingAs($admin)->post('/admin/course-catalog/courses', $courseData);

        $response->assertSessionHasErrors('name');
        $this->assertSame(0, $this->courseCount());
    }

    public function testCannotCreateACourseWithoutDescription(): void
    {
        $tenantId = $this->insertTenantRecord('Invalid Description Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-missing-description@example.test');
        $courseData = [
            'name' => 'Test Course',
        ];

        $response = $this->actingAs($admin)->post('/admin/course-catalog/courses', $courseData);

        $response->assertSessionHasErrors('description');
        $this->assertSame(0, $this->courseCountByName('Test Course'));
    }

    public function testNewCourseAppearsInCourseList(): void
    {
        $tenantId = $this->insertTenantRecord('List Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-list@example.test');
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
        $tenantId = $this->insertTenantRecord('Guest Edit Tenant');
        $courseId = $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');

        $response = $this->get("/admin/course-catalog/courses/{$courseId}/edit");

        $response->assertRedirect('/login');
    }

    public function testTheCourseEditPageRequiresAdminRole(): void
    {
        $tenantId = $this->insertTenantRecord('Role Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'student-edit@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

        $response = $this->actingAs($student)->get("/admin/course-catalog/courses/{$course->id}/edit");

        $response->assertStatus(403);
    }

    public function testTheCourseEditPageReturnsASuccessfulResult(): void
    {
        $tenantId = $this->insertTenantRecord('Edit Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-edit@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

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
        $tenantId = $this->insertTenantRecord('Missing Edit Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-missing-edit@example.test');

        $response = $this->actingAs($admin)->get('/admin/course-catalog/courses/99999/edit');

        $response->assertNotFound();
    }

    public function testCanUpdateACourseWithValidData(): void
    {
        $tenantId = $this->insertTenantRecord('Update Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-update@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

        $updatedData = [
            'name' => 'Updated Course Name',
            'description' => 'Updated course description',
        ];

        $response = $this->actingAs($admin)->put("/admin/course-catalog/courses/{$course->id}", $updatedData);

        $response->assertRedirect('/admin/course-catalog/courses?page=1');
        $updatedCourse = $this->findCourseById((int) $course->id);
        $this->assertSame('Updated Course Name', $updatedCourse->name);
        $this->assertSame('Updated course description', $updatedCourse->description);
    }

    public function testCannotUpdateACourseWithoutName(): void
    {
        $tenantId = $this->insertTenantRecord('Update Validation Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-update-validation@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

        $updatedData = [
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($admin)->put("/admin/course-catalog/courses/{$course->id}", $updatedData);

        $response->assertSessionHasErrors('name');
        $unchangedCourse = $this->findCourseById((int) $course->id);
        $this->assertSame($course->name, $unchangedCourse->name);
    }

    public function testCannotUpdateACourseWithoutDescription(): void
    {
        $tenantId = $this->insertTenantRecord('Description Validation Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-update-description@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

        $updatedData = [
            'name' => 'Updated Name',
        ];

        $response = $this->actingAs($admin)->put("/admin/course-catalog/courses/{$course->id}", $updatedData);

        $response->assertSessionHasErrors('description');
        $unchangedCourse = $this->findCourseById((int) $course->id);
        $this->assertSame($course->description, $unchangedCourse->description);
    }

    public function testUpdateCourseReturns404ForNonexistentCourse(): void
    {
        $tenantId = $this->insertTenantRecord('Missing Update Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-missing-update@example.test');
        $updatedData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($admin)->put('/admin/course-catalog/courses/99999', $updatedData);

        $response->assertNotFound();
    }

    public function testCourseListContainsEditLinks(): void
    {
        $tenantId = $this->insertTenantRecord('Link Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-link@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

        $response = $this->actingAs($admin)->get('/admin/course-catalog/courses');

        $response->assertOk();
        $response->assertSee("/admin/course-catalog/courses/{$course->id}/edit");
    }

    public function testEditFormDisplaysCurrentCourseValues(): void
    {
        $tenantId = $this->insertTenantRecord('Specific Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-specific@example.test');
        $courseData = [
            'name' => 'Specific Test Course',
            'description' => 'Specific test description',
        ];

        $this->actingAs($admin)->post('/admin/course-catalog/courses', $courseData);
        $course = $this->findCourseByName('Specific Test Course');

        $response = $this->actingAs($admin)->get("/admin/course-catalog/courses/{$course->id}/edit");

        $response->assertOk();
        $response->assertSee('value="Specific Test Course"', false);
        $response->assertSee('Specific test description');
    }

    public function testUpdatedCourseAppearsInCourseList(): void
    {
        $tenantId = $this->insertTenantRecord('Updated List Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-updated-list@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

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
        $tenantId = $this->insertTenantRecord('Cancel Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-cancel@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

        $response = $this->actingAs($admin)->get("/admin/course-catalog/courses/{$course->id}/edit");

        $response->assertOk();
        $response->assertSee('Cancel');
        $response->assertSee('href="' . route('course-catalog.admin.courses.index', ['page' => '1']) . '"', false);
    }

    public function testEditPagePreservesPageParameter(): void
    {
        $tenantId = $this->insertTenantRecord('Page Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-page@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

        $response = $this->actingAs($admin)->get("/admin/course-catalog/courses/{$course->id}/edit?page=2");

        $response->assertOk();
        $response->assertSee('value="2"', false);
    }

    public function testUpdateRedirectsToCorrectPage(): void
    {
        $tenantId = $this->insertTenantRecord('Redirect Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-redirect@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

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
        $tenantId = $this->insertTenantRecord('Cancel Page Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-cancel-page@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

        $response = $this->actingAs($admin)->get("/admin/course-catalog/courses/{$course->id}/edit?page=2");

        $response->assertOk();
        $response->assertSee('href="' . route('course-catalog.admin.courses.index', ['page' => '2']) . '"', false);
    }

    public function testUpdateDefaultsToPage1WhenPageNotProvided(): void
    {
        $tenantId = $this->insertTenantRecord('Default Page Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-default-page@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

        $updatedData = [
            'name' => 'Updated Name Without Page',
            'description' => 'Updated description without page',
        ];

        $response = $this->actingAs($admin)->put("/admin/course-catalog/courses/{$course->id}", $updatedData);

        $response->assertRedirect('/admin/course-catalog/courses?page=1');
    }

    public function testCanDeleteACourse(): void
    {
        $tenantId = $this->insertTenantRecord('Delete Tenant');
        $admin = $this->createUserRecord($tenantId, true, false, 'admin-delete@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

        $response = $this->actingAs($admin)->delete("/admin/course-catalog/courses/{$course->id}");

        $response->assertRedirect('/admin/course-catalog/courses');
        $this->assertFalse($this->courseExistsById((int) $course->id));
    }

    public function testStudentCannotAccessAdminCourseManagement(): void
    {
        $tenantId = $this->insertTenantRecord('Student Access Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'student-access@example.test');

        $response = $this->actingAs($student)->get('/admin/course-catalog/courses');
        $response->assertStatus(403);

        $response = $this->actingAs($student)->get('/admin/course-catalog/courses/create');
        $response->assertStatus(403);
    }

    public function testStudentCannotCreateCourse(): void
    {
        $tenantId = $this->insertTenantRecord('Student Create Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'student-create@example.test');
        $courseData = [
            'name' => 'Test Course',
            'description' => 'This is a test course description',
        ];

        $response = $this->actingAs($student)->post('/admin/course-catalog/courses', $courseData);

        $response->assertStatus(403);
        $this->assertSame(0, $this->courseCountByName('Test Course'));
    }

    public function testStudentCannotUpdateCourse(): void
    {
        $tenantId = $this->insertTenantRecord('Student Update Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'student-update@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

        $updatedData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($student)->put("/admin/course-catalog/courses/{$course->id}", $updatedData);

        $response->assertStatus(403);
    }

    public function testStudentCannotDeleteCourse(): void
    {
        $tenantId = $this->insertTenantRecord('Student Delete Tenant');
        $student = $this->createUserRecord($tenantId, false, true, 'student-delete@example.test');
        $this->insertCourseRecord($tenantId, 'Learn PHP', 'PHP fundamentals');
        $course = $this->firstCourse();

        $response = $this->actingAs($student)->delete("/admin/course-catalog/courses/{$course->id}");

        $response->assertStatus(403);
        $this->assertTrue($this->courseExistsById((int) $course->id));
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

    /**
     * @return object{id: int, tenant_id: int, name: string, description: string}
     */
    private function findCourseByName(string $name): object
    {
        /** @var object{id: int, tenant_id: int, name: string, description: string}|null $course */
        $course = DB::selectOne(
            'SELECT id, tenant_id, name, description
             FROM courses
             WHERE name = ?
             LIMIT 1',
            [$name],
        );

        $this->assertNotNull($course);

        return (object) [
            'id' => (int) $course->id,
            'tenant_id' => (int) $course->tenant_id,
            'name' => (string) $course->name,
            'description' => (string) $course->description,
        ];
    }

    /**
     * @return object{id: int, tenant_id: int, name: string, description: string}
     */
    private function findCourseById(int $id): object
    {
        /** @var object{id: int, tenant_id: int, name: string, description: string}|null $course */
        $course = DB::selectOne(
            'SELECT id, tenant_id, name, description
             FROM courses
             WHERE id = ?
             LIMIT 1',
            [$id],
        );

        $this->assertNotNull($course);

        return (object) [
            'id' => (int) $course->id,
            'tenant_id' => (int) $course->tenant_id,
            'name' => (string) $course->name,
            'description' => (string) $course->description,
        ];
    }

    private function courseExistsById(int $id): bool
    {
        $course = DB::selectOne(
            'SELECT 1 AS present
             FROM courses
             WHERE id = ?
             LIMIT 1',
            [$id],
        );

        return $course !== null;
    }

    private function courseCountByName(string $name): int
    {
        /** @var object{aggregate: int|string}|null $row */
        $row = DB::selectOne(
            'SELECT COUNT(*) AS aggregate
             FROM courses
             WHERE name = ?',
            [$name],
        );

        return $row !== null ? (int) $row->aggregate : 0;
    }

    private function courseCount(): int
    {
        /** @var object{aggregate: int|string}|null $row */
        $row = DB::selectOne('SELECT COUNT(*) AS aggregate FROM courses');

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
