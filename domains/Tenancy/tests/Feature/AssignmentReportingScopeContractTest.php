<?php

declare(strict_types=1);

namespace Tests\Domains\Tenancy\Feature;

use App\Domains\IdentityAccess\Models\User;
use App\Domains\Tenancy\Data\OrgNodeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Domains\Foundation\TestCase;

class AssignmentReportingScopeContractTest extends TestCase
{
    use RefreshDatabase;

    public function testScopeContractsResolveCompanyDepartmentAndTeamBoundaries(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Tenant', 4);
        $admin = $this->createUserRecord($tenantId, true, 'scope-contract-admin@example.test');

        $companyId = $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Acme Corp', 0, true);
        $businessUnitId = $this->insertOrgNodeRecord(
            $tenantId,
            $companyId,
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
        $teamId = $this->insertOrgNodeRecord($tenantId, $departmentId, OrgNodeType::Team, 'Platform Team', 3, true);

        $companyScope = $this->actingAs($admin)->getJson("/admin/tenancy/org-nodes/{$teamId}/scopes/company");
        $companyScope->assertOk();
        $companyScope->assertJsonPath('data.scope_type', OrgNodeType::Company->value);
        $companyScope->assertJsonPath('data.requested_node.id', $teamId);
        $companyScope->assertJsonPath('data.boundary_node.id', $companyId);
        $companyScope->assertJsonPath('data.descendant_ids.0', $businessUnitId);
        $companyScope->assertJsonPath('data.descendant_ids.1', $departmentId);
        $companyScope->assertJsonPath('data.descendant_ids.2', $teamId);

        $departmentScope = $this->actingAs($admin)->getJson("/admin/tenancy/org-nodes/{$teamId}/scopes/department");
        $departmentScope->assertOk();
        $departmentScope->assertJsonPath('data.scope_type', OrgNodeType::Department->value);
        $departmentScope->assertJsonPath('data.requested_node.id', $teamId);
        $departmentScope->assertJsonPath('data.boundary_node.id', $departmentId);
        $departmentScope->assertJsonPath('data.ancestors.0.id', $companyId);
        $departmentScope->assertJsonPath('data.ancestors.1.id', $businessUnitId);
        $departmentScope->assertJsonPath('data.descendant_ids.0', $teamId);

        $teamScope = $this->actingAs($admin)->getJson("/admin/tenancy/org-nodes/{$teamId}/scopes/team");
        $teamScope->assertOk();
        $teamScope->assertJsonPath('data.scope_type', OrgNodeType::Team->value);
        $teamScope->assertJsonPath('data.requested_node.id', $teamId);
        $teamScope->assertJsonPath('data.boundary_node.id', $teamId);
        $teamScope->assertJsonPath('data.descendant_subtree', []);
        $teamScope->assertJsonPath('data.descendant_ids', []);
    }

    public function testScopeContractsRejectUnavailableScopeBoundaries(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Tenant', 4);
        $admin = $this->createUserRecord($tenantId, true, 'invalid-scope-admin@example.test');

        $companyId = $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Acme Corp', 0, true);
        $businessUnitId = $this->insertOrgNodeRecord(
            $tenantId,
            $companyId,
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

        $companyDepartmentScope = $this->actingAs($admin)
            ->getJson("/admin/tenancy/org-nodes/{$companyId}/scopes/department");
        $companyDepartmentScope->assertStatus(422);
        $companyDepartmentScope->assertJsonValidationErrors(['scope']);

        $departmentTeamScope = $this->actingAs($admin)
            ->getJson("/admin/tenancy/org-nodes/{$departmentId}/scopes/team");
        $departmentTeamScope->assertStatus(422);
        $departmentTeamScope->assertJsonValidationErrors(['scope']);
    }

    public function testScopeContractsRejectCrossTenantReferences(): void
    {
        $tenantAId = $this->insertTenantRecord('Tenant A', 4);
        $tenantBId = $this->insertTenantRecord('Tenant B', 4);
        $admin = $this->createUserRecord($tenantAId, true, 'cross-tenant-scope-admin@example.test');

        $this->insertOrgNodeRecord($tenantAId, null, OrgNodeType::Company, 'Tenant A Root', 0, true);
        $foreignNodeId = $this->insertOrgNodeRecord($tenantBId, null, OrgNodeType::Company, 'Tenant B Root', 0, true);

        $response = $this->actingAs($admin)->getJson("/admin/tenancy/org-nodes/{$foreignNodeId}/scopes/company");

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
