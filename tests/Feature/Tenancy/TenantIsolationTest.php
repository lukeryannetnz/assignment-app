<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Tenancy\OrgNode;
use App\Models\Tenancy\Tenant;
use App\Models\CourseCatalog\Course;
use App\Models\IdentityAccess\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function testCourseIndexOnlyShowsCoursesForAuthenticatedUsersTenant(): void
    {
        $tenantA = Tenant::factory()->create(['name' => 'Tenant A']);
        $tenantB = Tenant::factory()->create(['name' => 'Tenant B']);

        $student = User::factory()->create(['tenant_id' => $tenantA->id]);

        Course::factory()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Tenant A Course',
            'description' => 'Visible in tenant A',
        ]);
        Course::factory()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Tenant B Course',
            'description' => 'Must stay hidden',
        ]);

        $response = $this->actingAs($student)->get('/courses');

        $response->assertOk();
        $response->assertSee('Tenant A Course');
        $response->assertDontSee('Tenant B Course');
    }

    public function testStudentCannotViewCourseFromAnotherTenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $student = User::factory()->create(['tenant_id' => $tenantA->id]);
        $externalCourse = Course::factory()->create(['tenant_id' => $tenantB->id]);

        $response = $this->actingAs($student)->get("/courses/{$externalCourse->id}");

        $response->assertNotFound();
    }

    public function testStudentCannotEnrollInCourseFromAnotherTenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $student = User::factory()->create(['tenant_id' => $tenantA->id]);
        $externalCourse = Course::factory()->create(['tenant_id' => $tenantB->id]);

        $response = $this->actingAs($student)->post("/courses/{$externalCourse->id}/enroll");

        $response->assertNotFound();
        $this->assertDatabaseMissing('course_user', [
            'user_id' => $student->id,
            'course_id' => $externalCourse->id,
        ]);
    }

    public function testAdminCannotPromoteUserFromAnotherTenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $admin = User::factory()->create(['tenant_id' => $tenantA->id, 'is_admin' => true]);
        $externalUser = User::factory()->create(['tenant_id' => $tenantB->id, 'is_admin' => false]);

        $response = $this->actingAs($admin)->post("/admin/users/{$externalUser->id}/promote");

        $response->assertNotFound();
        $this->assertDatabaseHas('users', [
            'id' => $externalUser->id,
            'tenant_id' => $tenantB->id,
            'is_admin' => false,
        ]);
    }

    public function testAdminCannotEditCourseFromAnotherTenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $admin = User::factory()->create(['tenant_id' => $tenantA->id, 'is_admin' => true]);
        $externalCourse = Course::factory()->create(['tenant_id' => $tenantB->id]);

        $response = $this->actingAs($admin)->get("/admin/courses/{$externalCourse->id}/edit");

        $response->assertNotFound();
    }

    public function testOrganizationEndpointsListOnlyCurrentTenantNodes(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $admin = User::factory()->create(['tenant_id' => $tenantA->id, 'is_admin' => true]);

        OrgNode::factory()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Tenant A Root',
        ]);

        OrgNode::factory()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Tenant B Root',
        ]);

        $response = $this->actingAs($admin)->getJson('/admin/org-nodes');

        $response->assertOk();
        $response->assertSee('Tenant A Root');
        $response->assertDontSee('Tenant B Root');
    }

    public function testOrganizationNodeCannotBeMovedUnderAnotherTenantsParent(): void
    {
        $tenantA = Tenant::factory()->create(['hierarchy_depth_limit' => 4]);
        $tenantB = Tenant::factory()->create(['hierarchy_depth_limit' => 4]);

        $admin = User::factory()->create(['tenant_id' => $tenantA->id, 'is_admin' => true]);

        $tenantANode = OrgNode::factory()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Tenant A Node',
            'depth' => 0,
            'node_type' => 'company',
        ]);

        $tenantBNode = OrgNode::factory()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Tenant B Node',
            'depth' => 0,
            'node_type' => 'company',
        ]);

        $response = $this->actingAs($admin)->postJson("/admin/org-nodes/{$tenantANode->id}/move", [
            'parent_id' => $tenantBNode->id,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('org_nodes', [
            'id' => $tenantANode->id,
            'tenant_id' => $tenantA->id,
            'parent_id' => null,
        ]);
    }

    public function testTenantMiddlewareBlocksAuthenticatedUsersWithoutTenant(): void
    {
        $tenant = Tenant::factory()->create();
        Course::factory()->create(['tenant_id' => $tenant->id]);

        $userWithoutTenant = User::factory()->create(['tenant_id' => null]);

        $response = $this->actingAs($userWithoutTenant)->get('/courses');

        $response->assertStatus(403);
    }
}
