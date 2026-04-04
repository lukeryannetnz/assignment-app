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

    public function testExampleOrganisationHierarchy(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Corp', 4);
        $admin = $this->createUserRecord($tenantId, true, 'acme-admin@example.test');

        $companyNodeId = $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Acme Corp', 0, true);
        $businessUnitId = $this->insertOrgNodeRecord(
            $tenantId,
            $companyNodeId,
            OrgNodeType::BusinessUnit,
            'North America',
            1,
            true,
        );
        $departmentId = $this->insertOrgNodeRecord(
            $tenantId,
            $businessUnitId,
            OrgNodeType::Department,
            'Engineering',
            2,
            true,
        );
        $teamId = $this->insertOrgNodeRecord(
            $tenantId,
            $departmentId,
            OrgNodeType::Team,
            'Platform Team',
            3,
            true,
        );

        /** @var object{tenant_count: int} $tenantCount */
        $tenantCount = $this->selectOne(
            'SELECT COUNT(*) AS tenant_count
             FROM tenants
             WHERE id = ?',
            [$tenantId],
        );
        $this->assertSame(1, (int) $tenantCount->tenant_count);

        /** @var list<object{tenant_id: int, parent_id: int|null, node_type: string, name: string}> $nodes */
        $nodes = $this->selectAll(
            'SELECT tenant_id, parent_id, node_type, name
             FROM org_nodes
             WHERE tenant_id = ?
             ORDER BY depth ASC, id ASC',
            [$tenantId],
        );
        $this->assertCount(4, $nodes);
        $this->assertSame('Acme Corp', $nodes[0]->name);
        $this->assertNull($nodes[0]->parent_id);
        $this->assertSame(OrgNodeType::Company->value, $nodes[0]->node_type);
        $this->assertSame($tenantId, (int) $nodes[1]->tenant_id);
        $this->assertSame($companyNodeId, (int) $nodes[1]->parent_id);
        $this->assertSame(OrgNodeType::BusinessUnit->value, $nodes[1]->node_type);
        $this->assertSame($businessUnitId, (int) $nodes[2]->parent_id);
        $this->assertSame(OrgNodeType::Department->value, $nodes[2]->node_type);
        $this->assertSame($departmentId, (int) $nodes[3]->parent_id);
        $this->assertSame(OrgNodeType::Team->value, $nodes[3]->node_type);

        $response = $this->actingAs($admin)->getJson("/admin/tenancy/org-nodes/{$companyNodeId}/scope");

        $response->assertOk();
        $response->assertJsonPath('data.node.name', 'Acme Corp');
        $response->assertJsonPath('data.node.node_type', OrgNodeType::Company->value);
        $response->assertJsonPath('data.descendant_subtree.0.name', 'North America');
        $response->assertJsonPath('data.descendant_subtree.1.name', 'Engineering');
        $response->assertJsonPath('data.descendant_subtree.2.name', 'Platform Team');
        $response->assertJsonPath('data.descendant_ids.0', $businessUnitId);
        $response->assertJsonPath('data.descendant_ids.1', $departmentId);
        $response->assertJsonPath('data.descendant_ids.2', $teamId);
    }

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

    /**
     * @param  array<int, mixed>  $bindings
     */
    private function selectOne(string $sql, array $bindings): object
    {
        $row = DB::selectOne($sql, $bindings);
        $this->assertNotNull($row);
        $this->assertIsObject($row);

        return $row;
    }

    /**
     * @param  array<int, mixed>  $bindings
     * @return list<object>
     */
    private function selectAll(string $sql, array $bindings): array
    {
        $rows = DB::select($sql, $bindings);

        return array_values($rows);
    }
}
