<?php

declare(strict_types=1);

namespace Tests\Domains\Tenancy\Feature;

use App\Domains\IdentityAccess\Models\User;
use App\Domains\Tenancy\Data\OrgNodeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Domains\Foundation\TestCase;

class PilotReadinessComponentTest extends TestCase
{
    use RefreshDatabase;

    public function testPilotReadinessPageShowsChecklistMetricsAndTemplateLibrary(): void
    {
        $tenantId = $this->insertTenantRecord('Pilot Tenant', 4);
        $admin = $this->createUserRecord($tenantId, true, 'pilot-readiness-admin@example.test');
        $rootNodeId = $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Pilot Tenant Root', 0, true);

        $tenantCreatedAt = now()->subHours(6)->format('Y-m-d H:i:s');
        $assignmentReadyAt = now()->subHours(2)->format('Y-m-d H:i:s');

        DB::insert(
            'INSERT INTO tenant_audit_logs
                (tenant_id, actor_user_id, action, auditable_type, auditable_id, metadata, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $tenantId,
                $admin->id,
                'tenant_created',
                'tenant',
                $tenantId,
                json_encode(['root_org_node_id' => $rootNodeId], JSON_THROW_ON_ERROR),
                $tenantCreatedAt,
                $tenantCreatedAt,
            ],
        );

        $businessUnitId = $this->insertOrgNodeRecord(
            $tenantId,
            $rootNodeId,
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

        DB::insert(
            'INSERT INTO tenant_audit_logs
                (tenant_id, actor_user_id, action, auditable_type, auditable_id, metadata, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $tenantId,
                $admin->id,
                'org_node_created',
                'org_node',
                $teamId,
                json_encode(['parent_id' => $departmentId, 'depth' => 3], JSON_THROW_ON_ERROR),
                $assignmentReadyAt,
                $assignmentReadyAt,
            ],
        );

        $response = $this->actingAs($admin)->get('/admin/tenancy/pilot-readiness');

        $response->assertOk();
        $response->assertSee('Pilot Readiness');
        $response->assertSee('Pilot Onboarding Checklist');
        $response->assertSee('Go / No-Go Checklist');
        $response->assertSee('Shared Onboarding Playbook');
        $response->assertSee('Hierarchy Starter Templates');
        $response->assertSee('Regional Divisions');
        $response->assertSee('Centralized Functions');
        $response->assertSee('School Network');
        $response->assertSee('4.00h');
        $response->assertSee('0.00%');
    }

    public function testPilotReadinessJsonSummarizesDurationAndIntegrityMetrics(): void
    {
        $tenantId = $this->insertTenantRecord('Metrics Tenant', 4);
        $admin = $this->createUserRecord($tenantId, true, 'pilot-readiness-json@example.test');
        $rootNodeId = $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Metrics Tenant Root', 0, true);

        $tenantCreatedAt = now()->subHours(5)->format('Y-m-d H:i:s');
        $teamCreatedAt = now()->subHours(1)->format('Y-m-d H:i:s');

        $businessUnitId = $this->insertOrgNodeRecord(
            $tenantId,
            $rootNodeId,
            OrgNodeType::BusinessUnit,
            'Operations',
            1,
            true,
        );
        $departmentId = $this->insertOrgNodeRecord(
            $tenantId,
            $businessUnitId,
            OrgNodeType::Department,
            'Implementation',
            2,
            true,
        );
        $teamId = $this->insertOrgNodeRecord(
            $tenantId,
            $departmentId,
            OrgNodeType::Team,
            'Launch Team',
            3,
            true,
        );

        DB::insert(
            'INSERT INTO tenant_audit_logs
                (tenant_id, actor_user_id, action, auditable_type, auditable_id, metadata, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?), (?, ?, ?, ?, ?, ?, ?, ?), (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $tenantId,
                $admin->id,
                'tenant_created',
                'tenant',
                $tenantId,
                json_encode(['root_org_node_id' => $rootNodeId], JSON_THROW_ON_ERROR),
                $tenantCreatedAt,
                $tenantCreatedAt,
                $tenantId,
                $admin->id,
                'org_node_created',
                'org_node',
                $teamId,
                json_encode(['parent_id' => $departmentId, 'depth' => 3], JSON_THROW_ON_ERROR),
                $teamCreatedAt,
                $teamCreatedAt,
                $tenantId,
                $admin->id,
                'hierarchy_integrity_error',
                'tenant',
                $tenantId,
                json_encode(['operation' => 'move_node', 'messages' => ['parent_id' => 'Cycle']], JSON_THROW_ON_ERROR),
                now()->format('Y-m-d H:i:s'),
                now()->format('Y-m-d H:i:s'),
            ],
        );

        $response = $this->actingAs($admin)->getJson('/admin/tenancy/pilot-readiness');

        $response->assertOk();
        $response->assertJsonPath('data.metrics.onboarding_duration_hours', 4);
        $response->assertJsonPath('data.metrics.hierarchy_write_count', 1);
        $response->assertJsonPath('data.metrics.hierarchy_integrity_error_count', 1);
        $response->assertJsonPath('data.metrics.hierarchy_integrity_error_rate', 50);
        $response->assertJsonPath('data.metrics.active_team_count', 1);
    }

    public function testTemplateDownloadRouteReturnsNamedPilotTemplate(): void
    {
        $tenantId = $this->insertTenantRecord('Templates Tenant', 4);
        $admin = $this->createUserRecord($tenantId, true, 'pilot-template-admin@example.test');
        $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Templates Tenant Root', 0, true);

        $response = $this->actingAs($admin)->get('/admin/tenancy/org-nodes/imports/templates/centralized-functions');

        $response->assertOk();
        $response->assertHeader(
            'content-disposition',
            'attachment; filename="centralized-functions-org-template.csv"',
        );
        $response->assertSee('corporate-services,,business_unit,Corporate Services', false);
        $response->assertSee('planning,finance,team,Planning and Analysis', false);
    }

    private function insertTenantRecord(string $name, int $hierarchyDepthLimit): int
    {
        DB::insert(
            'INSERT INTO tenants (name, status, plan_tier, hierarchy_depth_limit, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$name, 'active', 'enterprise_pilot', $hierarchyDepthLimit, now(), now()],
        );

        return (int) DB::getPdo()->lastInsertId();
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
            'INSERT INTO org_nodes
                (tenant_id, parent_id, node_type, name, depth, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$tenantId, $parentId, $nodeType->value, $name, $depth, $isActive, now(), now()],
        );

        return (int) DB::getPdo()->lastInsertId();
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
                $isAdmin ? 'Pilot Readiness Admin' : 'Pilot Readiness Member',
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

        $user = new User();
        $user->forceFill([
            'id' => (int) DB::getPdo()->lastInsertId(),
            'tenant_id' => $tenantId,
            'name' => $isAdmin ? 'Pilot Readiness Admin' : 'Pilot Readiness Member',
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
}
