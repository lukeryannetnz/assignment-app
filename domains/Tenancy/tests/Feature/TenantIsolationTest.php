<?php

declare(strict_types=1);

namespace Tests\Domains\Tenancy\Feature;

use App\Domains\IdentityAccess\Models\User;
use App\Domains\Tenancy\Data\OrgNodeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Domains\Foundation\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function testCourseIndexOnlyShowsCoursesForAuthenticatedUsersTenant(): void
    {
        $tenantAId = $this->insertTenantRecord('Tenant A');
        $tenantBId = $this->insertTenantRecord('Tenant B');

        $student = $this->createUserRecord($tenantAId, false, true, 'tenant-a-student@example.test');

        $this->insertCourseRecord($tenantAId, 'Tenant A Course', 'Visible in tenant A');
        $this->insertCourseRecord($tenantBId, 'Tenant B Course', 'Must stay hidden');

        $response = $this->actingAs($student)->get('/courses');

        $response->assertOk();
        $response->assertSee('Tenant A Course');
        $response->assertDontSee('Tenant B Course');
    }

    public function testStudentCannotViewCourseFromAnotherTenant(): void
    {
        $tenantAId = $this->insertTenantRecord('Tenant A');
        $tenantBId = $this->insertTenantRecord('Tenant B');

        $student = $this->createUserRecord($tenantAId, false, true, 'tenant-a-view@example.test');
        $externalCourseId = $this->insertCourseRecord($tenantBId, 'Tenant B Course', 'Must stay hidden');

        $response = $this->actingAs($student)->get("/courses/{$externalCourseId}");

        $response->assertNotFound();
    }

    public function testStudentCannotEnrollInCourseFromAnotherTenant(): void
    {
        $tenantAId = $this->insertTenantRecord('Tenant A');
        $tenantBId = $this->insertTenantRecord('Tenant B');

        $student = $this->createUserRecord($tenantAId, false, true, 'tenant-a-enroll@example.test');
        $externalCourseId = $this->insertCourseRecord($tenantBId, 'Tenant B Course', 'Must stay hidden');

        $response = $this->actingAs($student)->post("/courses/{$externalCourseId}/enroll");

        $response->assertNotFound();
        $this->assertSame(0, $this->countCourseEnrollments((int) $student->id, $externalCourseId));
    }

    public function testAdminCannotPromoteUserFromAnotherTenant(): void
    {
        $tenantAId = $this->insertTenantRecord('Tenant A');
        $tenantBId = $this->insertTenantRecord('Tenant B');

        $admin = $this->createUserRecord($tenantAId, true, true, 'tenant-a-admin@example.test');
        $externalUser = $this->createUserRecord($tenantBId, false, true, 'tenant-b-user@example.test');

        $response = $this->actingAs($admin)->post("/admin/identity-access/users/{$externalUser->id}/promote");

        $response->assertNotFound();
        $this->assertSame(
            1,
            $this->countUsers($externalUser->id, $tenantBId, false),
        );
    }

    public function testAdminCannotEditCourseFromAnotherTenant(): void
    {
        $tenantAId = $this->insertTenantRecord('Tenant A');
        $tenantBId = $this->insertTenantRecord('Tenant B');

        $admin = $this->createUserRecord($tenantAId, true, true, 'tenant-a-course-admin@example.test');
        $externalCourseId = $this->insertCourseRecord($tenantBId, 'Tenant B Course', 'Must stay hidden');

        $response = $this->actingAs($admin)->get("/admin/course-catalog/courses/{$externalCourseId}/edit");

        $response->assertNotFound();
    }

    public function testOrganizationEndpointsListOnlyCurrentTenantNodes(): void
    {
        $tenantAId = $this->insertTenantRecord('Tenant A');
        $tenantBId = $this->insertTenantRecord('Tenant B');

        $admin = $this->createUserRecord($tenantAId, true, true, 'tenant-a-org-admin@example.test');

        $this->insertOrgNodeRecord($tenantAId, null, OrgNodeType::Company, 'Tenant A Root', 0, true);
        $this->insertOrgNodeRecord($tenantBId, null, OrgNodeType::Company, 'Tenant B Root', 0, true);

        $response = $this->actingAs($admin)->getJson('/admin/tenancy/org-nodes');

        $response->assertOk();
        $response->assertSee('Tenant A Root');
        $response->assertDontSee('Tenant B Root');
    }

    public function testOrganizationNodeCannotBeMovedUnderAnotherTenantsParent(): void
    {
        $tenantAId = $this->insertTenantRecord('Tenant A', 4);
        $tenantBId = $this->insertTenantRecord('Tenant B', 4);

        $admin = $this->createUserRecord($tenantAId, true, true, 'tenant-a-move-admin@example.test');

        $tenantANodeId = $this->insertOrgNodeRecord($tenantAId, null, OrgNodeType::Company, 'Tenant A Node', 0, true);
        $tenantBNodeId = $this->insertOrgNodeRecord($tenantBId, null, OrgNodeType::Company, 'Tenant B Node', 0, true);

        $response = $this->actingAs($admin)->postJson("/admin/tenancy/org-nodes/{$tenantANodeId}/move", [
            'parent_id' => $tenantBNodeId,
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, $this->countOrgNodeParent($tenantANodeId, $tenantAId, $tenantBNodeId));
    }

    public function testDashboardRouteIncludesTenantMiddleware(): void
    {
        $route = Route::getRoutes()->getByName('course-catalog.dashboard');

        $this->assertNotNull($route);
        $this->assertContains('tenant', $route->gatherMiddleware());
    }

    private function insertTenantRecord(string $name, int $hierarchyDepthLimit = 4): int
    {
        DB::insert(
            'INSERT INTO tenants (name, status, plan_tier, hierarchy_depth_limit, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$name, 'active', 'enterprise_pilot', $hierarchyDepthLimit, now(), now()],
        );

        return $this->lastInsertId();
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

    private function insertOrgNodeRecord(
        int $tenantId,
        ?int $parentId,
        OrgNodeType $nodeType,
        string $name,
        int $depth,
        bool $isActive,
    ): int {
        DB::insert(
            'INSERT INTO org_nodes (tenant_id, parent_id, node_type, name, depth, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$tenantId, $parentId, $nodeType->value, $name, $depth, $isActive, now(), now()],
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

    private function countCourseEnrollments(int $userId, int $courseId): int
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

    private function countUsers(int $userId, int $tenantId, bool $isAdmin): int
    {
        /** @var object{aggregate: int|string}|null $row */
        $row = DB::selectOne(
            'SELECT COUNT(*) AS aggregate
             FROM users
             WHERE id = ?
               AND tenant_id = ?
               AND is_admin = ?',
            [$userId, $tenantId, $isAdmin],
        );

        return $row !== null ? (int) $row->aggregate : 0;
    }

    private function countOrgNodeParent(int $nodeId, int $tenantId, int $expectedParentId): int
    {
        /** @var object{aggregate: int|string}|null $row */
        $row = DB::selectOne(
            'SELECT COUNT(*) AS aggregate
             FROM org_nodes
             WHERE id = ?
               AND tenant_id = ?
               AND parent_id = ?',
            [$nodeId, $tenantId, $expectedParentId],
        );

        return $row !== null ? (int) $row->aggregate : 0;
    }

    private function lastInsertId(): int
    {
        return (int) DB::getPdo()->lastInsertId();
    }
}
