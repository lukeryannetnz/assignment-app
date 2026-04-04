<?php

declare(strict_types=1);

namespace Tests\Domains\Tenancy\Feature;

use App\Domains\IdentityAccess\Models\User;
use App\Domains\Tenancy\Data\OrgNodeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Domains\Foundation\TestCase;

class OrganizationHierarchyComponentTest extends TestCase
{
    use RefreshDatabase;

    public function testOrganizationNodeRoutesRequireAuthenticationAndAdminRole(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Tenant', 4);
        $user = $this->createUserRecord($tenantId, false, 'member@example.test');
        $rootId = $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Acme Root', 0, true);

        $unauthenticatedResponse = $this->get("/admin/tenancy/org-nodes/{$rootId}/scope");
        $unauthenticatedResponse->assertRedirect('/login');

        $forbiddenResponse = $this->actingAs($user)->postJson('/admin/tenancy/org-nodes', [
            'name' => 'Platform Dept',
            'node_type' => OrgNodeType::Department->value,
            'parent_id' => $rootId,
        ]);
        $forbiddenResponse->assertForbidden();
    }

    public function testAdminCanCreateListAndRenameOrganizationNodesThroughRoutes(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Tenant', 4);
        $admin = $this->createUserRecord($tenantId, true, 'admin@example.test');
        $rootId = $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Acme Root', 0, true);

        $externalTenantId = $this->insertTenantRecord('External Tenant', 4);
        $this->insertOrgNodeRecord($externalTenantId, null, OrgNodeType::Company, 'External Root', 0, true);

        $createResponse = $this->actingAs($admin)->postJson('/admin/tenancy/org-nodes', [
            'name' => 'Platform Dept',
            'node_type' => OrgNodeType::Department->value,
            'parent_id' => $rootId,
        ]);

        $createResponse->assertCreated();
        $createResponse->assertJsonPath('data.node_type', OrgNodeType::Department->value);
        $createResponse->assertJsonPath('data.parent_id', $rootId);
        $createResponse->assertJsonPath('data.depth', 1);
        $createResponse->assertJsonPath('data.is_active', true);

        $departmentId = $this->responseInt($createResponse, 'data.id');

        /** @var object{tenant_id: int, parent_id: int|null, node_type: string, name: string, depth: int, is_active: int|bool} $department */
        $department = $this->selectOne(
            'SELECT tenant_id, parent_id, node_type, name, depth, is_active
             FROM org_nodes
             WHERE id = ?
             LIMIT 1',
            [$departmentId],
        );
        $this->assertSame($tenantId, (int) $department->tenant_id);
        $this->assertSame($rootId, (int) $department->parent_id);
        $this->assertSame(OrgNodeType::Department->value, $department->node_type);
        $this->assertSame('Platform Dept', $department->name);
        $this->assertSame(1, (int) $department->depth);
        $this->assertTrue((bool) $department->is_active);

        $listResponse = $this->actingAs($admin)->getJson('/admin/tenancy/org-nodes');

        $listResponse->assertOk();
        $listResponse->assertSee('Acme Root');
        $listResponse->assertSee('Platform Dept');
        $listResponse->assertDontSee('External Root');

        $updateResponse = $this->actingAs($admin)->putJson("/admin/tenancy/org-nodes/{$departmentId}", [
            'name' => 'Platform Engineering',
        ]);

        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('data.name', 'Platform Engineering');

        /** @var object{name: string} $updatedNode */
        $updatedNode = $this->selectOne(
            'SELECT name
             FROM org_nodes
             WHERE id = ?
             LIMIT 1',
            [$departmentId],
        );
        $this->assertSame('Platform Engineering', $updatedNode->name);

        /** @var object{metadata: string|null} $audit */
        $audit = $this->selectOne(
            'SELECT metadata
             FROM tenant_audit_logs
             WHERE tenant_id = ?
               AND action = ?
               AND auditable_id = ?
             ORDER BY id DESC
             LIMIT 1',
            [$tenantId, 'org_node_updated', $departmentId],
        );

        /** @var array<string, mixed> $auditMetadata */
        $auditMetadata = json_decode((string) $audit->metadata, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Platform Dept', $auditMetadata['old_name']);
        $this->assertSame('Platform Engineering', $auditMetadata['new_name']);
    }

    public function testMoveRouteUpdatesParentSubtreeDepthsAndAuditLog(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Tenant', 4);
        $admin = $this->createUserRecord($tenantId, true, 'move-admin@example.test');

        $rootId = $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Acme Root', 0, true);
        $departmentAId = $this->insertOrgNodeRecord($tenantId, $rootId, OrgNodeType::Department, 'Operations', 1, true);
        $departmentBId = $this->insertOrgNodeRecord(
            $tenantId,
            $rootId,
            OrgNodeType::Department,
            'Engineering',
            1,
            true,
        );
        $teamId = $this->insertOrgNodeRecord($tenantId, $departmentAId, OrgNodeType::Team, 'Platform Team', 2, true);

        $response = $this->actingAs($admin)->postJson("/admin/tenancy/org-nodes/{$departmentAId}/move", [
            'parent_id' => $departmentBId,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.parent_id', $departmentBId);
        $response->assertJsonPath('data.depth', 2);

        /** @var object{parent_id: int|null, depth: int} $movedDepartment */
        $movedDepartment = $this->selectOne(
            'SELECT parent_id, depth
             FROM org_nodes
             WHERE id = ?
             LIMIT 1',
            [$departmentAId],
        );
        $this->assertSame($departmentBId, (int) $movedDepartment->parent_id);
        $this->assertSame(2, (int) $movedDepartment->depth);

        /** @var object{parent_id: int|null, depth: int} $team */
        $team = $this->selectOne(
            'SELECT parent_id, depth
             FROM org_nodes
             WHERE id = ?
             LIMIT 1',
            [$teamId],
        );
        $this->assertSame($departmentAId, (int) $team->parent_id);
        $this->assertSame(3, (int) $team->depth);

        /** @var object{metadata: string|null} $audit */
        $audit = $this->selectOne(
            'SELECT metadata
             FROM tenant_audit_logs
             WHERE tenant_id = ?
               AND action = ?
               AND auditable_id = ?
             ORDER BY id DESC
             LIMIT 1',
            [$tenantId, 'org_node_moved', $departmentAId],
        );

        /** @var array<string, mixed> $auditMetadata */
        $auditMetadata = json_decode((string) $audit->metadata, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($rootId, $auditMetadata['old_parent_id']);
        $this->assertSame($departmentBId, $auditMetadata['new_parent_id']);
    }

    public function testHierarchyRoutesRejectCycleCrossTenantAndDepthViolations(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Tenant', 2);
        $foreignTenantId = $this->insertTenantRecord('Foreign Tenant', 4);
        $admin = $this->createUserRecord($tenantId, true, 'constraint-admin@example.test');

        $rootId = $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Acme Root', 0, true);
        $departmentId = $this->insertOrgNodeRecord($tenantId, $rootId, OrgNodeType::Department, 'Engineering', 1, true);
        $teamId = $this->insertOrgNodeRecord($tenantId, $departmentId, OrgNodeType::Team, 'Platform Team', 2, true);
        $foreignRootId = $this->insertOrgNodeRecord(
            $foreignTenantId,
            null,
            OrgNodeType::Company,
            'Foreign Root',
            0,
            true,
        );

        $createTooDeepResponse = $this->actingAs($admin)->postJson('/admin/tenancy/org-nodes', [
            'name' => 'Impossible Team',
            'node_type' => OrgNodeType::Team->value,
            'parent_id' => $teamId,
        ]);
        $createTooDeepResponse->assertStatus(422);
        $createTooDeepResponse->assertJsonValidationErrors(['parent_id']);

        $cycleResponse = $this->actingAs($admin)->postJson("/admin/tenancy/org-nodes/{$departmentId}/move", [
            'parent_id' => $teamId,
        ]);
        $cycleResponse->assertStatus(422);
        $cycleResponse->assertJsonValidationErrors(['parent_id']);

        $crossTenantResponse = $this->actingAs($admin)->postJson("/admin/tenancy/org-nodes/{$departmentId}/move", [
            'parent_id' => $foreignRootId,
        ]);
        $crossTenantResponse->assertStatus(422);
        $crossTenantResponse->assertJsonValidationErrors(['node']);

        $otherDepartmentId = $this->insertOrgNodeRecord(
            $tenantId,
            $rootId,
            OrgNodeType::Department,
            'Operations',
            1,
            true,
        );

        $moveTooDeepResponse = $this->actingAs($admin)->postJson("/admin/tenancy/org-nodes/{$departmentId}/move", [
            'parent_id' => $otherDepartmentId,
        ]);
        $moveTooDeepResponse->assertStatus(422);
        $moveTooDeepResponse->assertJsonValidationErrors(['parent_id']);
    }

    public function testDeactivateAndReactivateRoutesEnforceHierarchyStateRules(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Tenant', 4);
        $admin = $this->createUserRecord($tenantId, true, 'state-admin@example.test');

        $rootId = $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Acme Root', 0, true);
        $departmentId = $this->insertOrgNodeRecord($tenantId, $rootId, OrgNodeType::Department, 'Engineering', 1, true);
        $teamId = $this->insertOrgNodeRecord($tenantId, $departmentId, OrgNodeType::Team, 'Platform Team', 2, true);

        $rejectDeactivateResponse = $this->actingAs($admin)
            ->postJson("/admin/tenancy/org-nodes/{$departmentId}/deactivate");
        $rejectDeactivateResponse->assertStatus(422);
        $rejectDeactivateResponse->assertJsonValidationErrors(['node']);

        $deactivateLeafResponse = $this->actingAs($admin)
            ->postJson("/admin/tenancy/org-nodes/{$teamId}/deactivate");
        $deactivateLeafResponse->assertOk();
        $deactivateLeafResponse->assertJsonPath('data.is_active', false);

        /** @var object{is_active: int|bool} $teamAfterDeactivation */
        $teamAfterDeactivation = $this->selectOne(
            'SELECT is_active
             FROM org_nodes
             WHERE id = ?
             LIMIT 1',
            [$teamId],
        );
        $this->assertFalse((bool) $teamAfterDeactivation->is_active);

        $deactivateParentResponse = $this->actingAs($admin)
            ->postJson("/admin/tenancy/org-nodes/{$departmentId}/deactivate");
        $deactivateParentResponse->assertOk();
        $deactivateParentResponse->assertJsonPath('data.is_active', false);

        $reactivateLeafWhileParentInactive = $this->actingAs($admin)
            ->postJson("/admin/tenancy/org-nodes/{$teamId}/reactivate");
        $reactivateLeafWhileParentInactive->assertStatus(422);
        $reactivateLeafWhileParentInactive->assertJsonValidationErrors(['node']);

        $reactivateParentResponse = $this->actingAs($admin)
            ->postJson("/admin/tenancy/org-nodes/{$departmentId}/reactivate");
        $reactivateParentResponse->assertOk();
        $reactivateParentResponse->assertJsonPath('data.is_active', true);

        $reactivateLeafResponse = $this->actingAs($admin)
            ->postJson("/admin/tenancy/org-nodes/{$teamId}/reactivate");
        $reactivateLeafResponse->assertOk();
        $reactivateLeafResponse->assertJsonPath('data.is_active', true);

        /** @var object{action: string} $deactivatedAudit */
        $deactivatedAudit = $this->selectOne(
            'SELECT action
             FROM tenant_audit_logs
             WHERE tenant_id = ?
               AND auditable_id = ?
               AND action = ?
             ORDER BY id DESC
             LIMIT 1',
            [$tenantId, $teamId, 'org_node_deactivated'],
        );
        $this->assertSame('org_node_deactivated', $deactivatedAudit->action);

        /** @var object{metadata: string|null} $reactivatedAudit */
        $reactivatedAudit = $this->selectOne(
            'SELECT metadata
             FROM tenant_audit_logs
             WHERE tenant_id = ?
               AND auditable_id = ?
               AND action = ?
             ORDER BY id DESC
             LIMIT 1',
            [$tenantId, $teamId, 'org_node_updated'],
        );

        /** @var array<string, mixed> $reactivationMetadata */
        $reactivationMetadata = json_decode((string) $reactivatedAudit->metadata, true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue((bool) $reactivationMetadata['reactivated']);
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

    private function lastInsertId(): int
    {
        return (int) DB::getPdo()->lastInsertId();
    }

    /**
     * @param  TestResponse<\Symfony\Component\HttpFoundation\Response>  $response
     */
    private function responseInt(TestResponse $response, string $path): int
    {
        $value = $response->json($path);

        if (!is_int($value)) {
            throw new \RuntimeException(sprintf('Expected integer at path "%s".', $path));
        }

        return $value;
    }
}
