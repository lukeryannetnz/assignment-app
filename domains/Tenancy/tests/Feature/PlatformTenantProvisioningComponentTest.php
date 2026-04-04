<?php

declare(strict_types=1);

namespace Tests\Domains\Tenancy\Feature;

use App\Domains\IdentityAccess\Models\User;
use App\Domains\Tenancy\Data\OrgNodeType;
use App\Domains\Tenancy\Events\TenantCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Tests\Domains\Foundation\TestCase;

class PlatformTenantProvisioningComponentTest extends TestCase
{
    use RefreshDatabase;

    public function testProvisioningRouteRequiresAuthentication(): void
    {
        $response = $this->post('/admin/tenancy/tenants', [
            'name' => 'Acme Learning',
        ]);

        $response->assertRedirect('/login');
    }

    public function testProvisioningRouteRequiresAdminRole(): void
    {
        $user = $this->createUserRecord(null, false, 'member@example.test');

        $response = $this->actingAs($user)->post('/admin/tenancy/tenants', [
            'name' => 'Acme Learning',
        ]);

        $response->assertForbidden();
    }

    public function testProvisioningCreatesTenantShellRootNodeAuditRowsAndDispatchesEvent(): void
    {
        Event::fake();

        $admin = $this->createUserRecord(null, true, 'admin@example.test');

        $response = $this->actingAs($admin)->postJson('/admin/tenancy/tenants', [
            'name' => 'Acme Learning',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.tenant.name', 'Acme Learning');
        $response->assertJsonPath('data.tenant.status', 'active');
        $response->assertJsonPath('data.tenant.plan_tier', 'enterprise_pilot');
        $response->assertJsonPath('data.tenant.hierarchy_depth_limit', 4);
        $response->assertJsonPath('data.root_org_node.node_type', OrgNodeType::Company->value);
        $response->assertJsonPath('data.root_org_node.parent_id', null);
        $response->assertJsonPath('data.root_org_node.depth', 0);
        $response->assertJsonPath('data.root_org_node.is_active', true);

        $tenantId = $this->responseInt($response, 'data.tenant.id');
        $rootNodeId = $this->responseInt($response, 'data.root_org_node.id');

        /** @var object{name: string, status: string, plan_tier: string, hierarchy_depth_limit: int} $tenant */
        $tenant = $this->selectOne(
            'SELECT id, name, status, plan_tier, hierarchy_depth_limit
             FROM tenants
             WHERE id = ?
             LIMIT 1',
            [$tenantId],
        );
        $this->assertSame('Acme Learning', $tenant->name);
        $this->assertSame('active', $tenant->status);
        $this->assertSame('enterprise_pilot', $tenant->plan_tier);
        $this->assertSame(4, (int) $tenant->hierarchy_depth_limit);

        /** @var object{tenant_id: int, parent_id: int|null, node_type: string, name: string, depth: int, is_active: int|bool} $rootNode */
        $rootNode = $this->selectOne(
            'SELECT id, tenant_id, parent_id, node_type, name, depth, is_active
             FROM org_nodes
             WHERE id = ?
             LIMIT 1',
            [$rootNodeId],
        );
        $this->assertSame($tenantId, (int) $rootNode->tenant_id);
        $this->assertNull($rootNode->parent_id);
        $this->assertSame(OrgNodeType::Company->value, $rootNode->node_type);
        $this->assertSame('Acme Learning', $rootNode->name);
        $this->assertSame(0, (int) $rootNode->depth);
        $this->assertTrue((bool) $rootNode->is_active);

        /** @var object{auditable_id: int, metadata: string|null} $tenantAudit */
        $tenantAudit = $this->selectOne(
            'SELECT auditable_id, metadata
             FROM tenant_audit_logs
             WHERE tenant_id = ?
               AND action = ?
               AND auditable_type = ?
             ORDER BY id DESC
             LIMIT 1',
            [$tenantId, 'tenant_created', 'tenant'],
        );
        $this->assertSame($tenantId, (int) $tenantAudit->auditable_id);

        /** @var array<string, mixed> $tenantAuditMetadata */
        $tenantAuditMetadata = json_decode((string) $tenantAudit->metadata, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Acme Learning', $tenantAuditMetadata['name']);
        $this->assertSame('enterprise_pilot', $tenantAuditMetadata['plan_tier']);
        $this->assertSame(4, $tenantAuditMetadata['hierarchy_depth_limit']);
        $this->assertSame($rootNodeId, $tenantAuditMetadata['root_org_node_id']);

        /** @var object{auditable_id: int} $orgNodeAudit */
        $orgNodeAudit = $this->selectOne(
            'SELECT auditable_id
             FROM tenant_audit_logs
             WHERE tenant_id = ?
               AND action = ?
               AND auditable_type = ?
             ORDER BY id DESC
             LIMIT 1',
            [$tenantId, 'org_node_created', 'org_node'],
        );
        $this->assertSame($rootNodeId, (int) $orgNodeAudit->auditable_id);

        Event::assertDispatched(TenantCreated::class, static function (TenantCreated $event) use (
            $admin,
            $rootNodeId,
            $tenantId,
        ): bool {
            return $event->tenantId === $tenantId
                && $event->actorUserId === (int) $admin->id
                && $event->rootOrgNodeId === $rootNodeId;
        });
    }

    public function testProvisioningUsesDefaultsWhenOptionalFieldsAreOmitted(): void
    {
        $admin = $this->createUserRecord(null, true, 'defaults-admin@example.test');

        $response = $this->actingAs($admin)->postJson('/admin/tenancy/tenants', [
            'name' => 'Northwind',
        ]);

        $response->assertCreated();

        $tenantId = $this->responseInt($response, 'data.tenant.id');

        /** @var object{name: string, status: string, plan_tier: string, hierarchy_depth_limit: int} $tenant */
        $tenant = $this->selectOne(
            'SELECT name, status, plan_tier, hierarchy_depth_limit
             FROM tenants
             WHERE id = ?
             LIMIT 1',
            [$tenantId],
        );
        $this->assertSame('Northwind', $tenant->name);
        $this->assertSame('active', $tenant->status);
        $this->assertSame('enterprise_pilot', $tenant->plan_tier);
        $this->assertSame(4, (int) $tenant->hierarchy_depth_limit);

        /** @var object{parent_id: int|null, node_type: string, name: string, depth: int, is_active: int|bool} $rootNode */
        $rootNode = $this->selectOne(
            'SELECT tenant_id, parent_id, node_type, name, depth, is_active
             FROM org_nodes
             WHERE tenant_id = ?
             ORDER BY id DESC
             LIMIT 1',
            [$tenantId],
        );
        $this->assertNull($rootNode->parent_id);
        $this->assertSame(OrgNodeType::Company->value, $rootNode->node_type);
        $this->assertSame('Northwind', $rootNode->name);
        $this->assertSame(0, (int) $rootNode->depth);
        $this->assertTrue((bool) $rootNode->is_active);
    }

    public function testProvisioningAllowsExplicitRootOrgName(): void
    {
        $admin = $this->createUserRecord(null, true, 'root-name-admin@example.test');

        $response = $this->actingAs($admin)->postJson('/admin/tenancy/tenants', [
            'name' => 'Contoso',
            'root_org_name' => 'Contoso Global',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.root_org_node.name', 'Contoso Global');

        $rootNodeId = $this->responseInt($response, 'data.root_org_node.id');

        /** @var object{name: string, node_type: string, depth: int} $rootNode */
        $rootNode = $this->selectOne(
            'SELECT name, node_type, depth
             FROM org_nodes
             WHERE id = ?
             LIMIT 1',
            [$rootNodeId],
        );
        $this->assertSame('Contoso Global', $rootNode->name);
        $this->assertSame(OrgNodeType::Company->value, $rootNode->node_type);
        $this->assertSame(0, (int) $rootNode->depth);
    }

    public function testTenantAdminCanViewAndUpdateCurrentTenantThroughRoutes(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Learning', 'active', 'enterprise_pilot', 4);
        $admin = $this->createUserRecord($tenantId, true, 'tenant-admin@example.test');

        $showResponse = $this->actingAs($admin)->getJson('/admin/tenancy/tenant');

        $showResponse->assertOk();
        $showResponse->assertJson([
            'id' => $tenantId,
            'name' => 'Acme Learning',
            'status' => 'active',
            'plan_tier' => 'enterprise_pilot',
            'hierarchy_depth_limit' => 4,
        ]);

        $updateResponse = $this->actingAs($admin)->putJson('/admin/tenancy/tenant', [
            'name' => 'Acme Enterprise',
            'status' => 'inactive',
            'plan_tier' => 'enterprise',
            'hierarchy_depth_limit' => 3,
        ]);

        $updateResponse->assertOk();
        $updateResponse->assertJson([
            'id' => $tenantId,
            'name' => 'Acme Enterprise',
            'status' => 'inactive',
            'plan_tier' => 'enterprise',
            'hierarchy_depth_limit' => 3,
        ]);

        /** @var object{name: string, status: string, plan_tier: string, hierarchy_depth_limit: int} $tenant */
        $tenant = $this->selectOne(
            'SELECT name, status, plan_tier, hierarchy_depth_limit
             FROM tenants
             WHERE id = ?
             LIMIT 1',
            [$tenantId],
        );
        $this->assertSame('Acme Enterprise', $tenant->name);
        $this->assertSame('inactive', $tenant->status);
        $this->assertSame('enterprise', $tenant->plan_tier);
        $this->assertSame(3, (int) $tenant->hierarchy_depth_limit);

        /** @var object{metadata: string|null} $audit */
        $audit = $this->selectOne(
            'SELECT metadata
             FROM tenant_audit_logs
             WHERE tenant_id = ?
               AND action = ?
               AND auditable_type = ?
               AND auditable_id = ?
             ORDER BY id DESC
             LIMIT 1',
            [$tenantId, 'tenant_updated', 'tenant', $tenantId],
        );

        /** @var array<string, mixed> $auditMetadata */
        $auditMetadata = json_decode((string) $audit->metadata, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Acme Enterprise', $auditMetadata['name']);
        $this->assertSame('inactive', $auditMetadata['status']);
        $this->assertSame('enterprise', $auditMetadata['plan_tier']);
        $this->assertSame(3, $auditMetadata['hierarchy_depth_limit']);
    }

    private function insertTenantRecord(string $name, string $status, string $planTier, int $hierarchyDepthLimit): int
    {
        DB::insert(
            'INSERT INTO tenants (name, status, plan_tier, hierarchy_depth_limit, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$name, $status, $planTier, $hierarchyDepthLimit, now(), now()],
        );

        return $this->lastInsertId();
    }

    private function createUserRecord(?int $tenantId, bool $isAdmin, string $email): User
    {
        if ($tenantId === null) {
            $tenantId = $this->insertTenantRecord('Operator Tenant', 'active', 'enterprise_pilot', 4);
        }

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
                'rememberme',
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
            'remember_token' => 'rememberme',
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
