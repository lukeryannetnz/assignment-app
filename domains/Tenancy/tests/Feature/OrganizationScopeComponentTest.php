<?php

declare(strict_types=1);

namespace Tests\Domains\Tenancy\Feature;

use App\Domains\IdentityAccess\Models\User;
use App\Domains\Tenancy\Data\OrgNodeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Domains\Foundation\TestCase;

class OrganizationScopeComponentTest extends TestCase
{
    use RefreshDatabase;

    public function testScopeRouteReturnsAncestorsDescendantsAndDescendantIds(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Tenant', 4);
        $admin = $this->createUserRecord($tenantId, true, 'scope-admin@example.test');

        $rootId = $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Acme Root', 0, true);
        $departmentId = $this->insertOrgNodeRecord(
            $tenantId,
            $rootId,
            OrgNodeType::Department,
            'Platform Dept',
            1,
            true,
        );
        $teamId = $this->insertOrgNodeRecord($tenantId, $departmentId, OrgNodeType::Team, 'Platform Team', 2, true);

        $response = $this->actingAs($admin)->getJson("/admin/tenancy/org-nodes/{$departmentId}/scope");

        $response->assertOk();
        $response->assertJsonPath('data.node.name', 'Platform Dept');
        $response->assertJsonPath('data.node.parent_id', $rootId);
        $response->assertJsonPath('data.ancestors.0.name', 'Acme Root');
        $response->assertJsonPath('data.descendant_subtree.0.name', 'Platform Team');
        $response->assertJsonPath('data.descendant_ids.0', $teamId);
    }

    public function testLeafScopeReturnsEmptyDescendantCollections(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Tenant', 4);
        $admin = $this->createUserRecord($tenantId, true, 'leaf-admin@example.test');

        $rootId = $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Acme Root', 0, true);
        $departmentId = $this->insertOrgNodeRecord(
            $tenantId,
            $rootId,
            OrgNodeType::Department,
            'Platform Dept',
            1,
            true,
        );
        $teamId = $this->insertOrgNodeRecord($tenantId, $departmentId, OrgNodeType::Team, 'Platform Team', 2, true);

        $response = $this->actingAs($admin)->getJson("/admin/tenancy/org-nodes/{$teamId}/scope");

        $response->assertOk();
        $response->assertJsonPath('data.ancestors.0.name', 'Acme Root');
        $response->assertJsonPath('data.ancestors.1.name', 'Platform Dept');
        $response->assertJsonPath('data.descendant_subtree', []);
        $response->assertJsonPath('data.descendant_ids', []);
    }

    public function testScopeRouteRejectsCrossTenantAccess(): void
    {
        $tenantAId = $this->insertTenantRecord('Tenant A', 4);
        $tenantBId = $this->insertTenantRecord('Tenant B', 4);
        $admin = $this->createUserRecord($tenantAId, true, 'cross-tenant-admin@example.test');

        $this->insertOrgNodeRecord($tenantAId, null, OrgNodeType::Company, 'Tenant A Root', 0, true);
        $foreignNodeId = $this->insertOrgNodeRecord($tenantBId, null, OrgNodeType::Company, 'Tenant B Root', 0, true);

        $response = $this->actingAs($admin)->getJson("/admin/tenancy/org-nodes/{$foreignNodeId}/scope");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['node']);
    }

    private function insertTenantRecord(string $name, int $hierarchyDepthLimit): int
    {
        DB::insert(
            'INSERT INTO tenants (name, status, plan_tier, hierarchy_depth_limit, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$name, 'active', 'enterprise_pilot', $hierarchyDepthLimit, now(), now()],
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

    private function createUserRecord(?int $tenantId, bool $isAdmin, string $email): User
    {
        DB::insert(
            'INSERT INTO users
                (tenant_id, name, email, email_verified_at, password, remember_token,
                 is_student, is_admin, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $tenantId,
                $isAdmin ? 'Admin User' : 'Member User',
                $email,
                now(),
                bcrypt('password'),
                substr(md5($email), 0, 10),
                true,
                $isAdmin,
                now(),
                now(),
            ],
        );

        return $this->makeUser((int) $this->lastInsertId(), $tenantId, $isAdmin, $email);
    }

    private function makeUser(int $id, ?int $tenantId, bool $isAdmin, string $email): User
    {
        $user = new User();
        $user->forceFill([
            'id' => $id,
            'tenant_id' => $tenantId,
            'name' => $isAdmin ? 'Admin User' : 'Member User',
            'email' => $email,
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'remember_token' => substr(md5($email), 0, 10),
            'is_student' => true,
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
