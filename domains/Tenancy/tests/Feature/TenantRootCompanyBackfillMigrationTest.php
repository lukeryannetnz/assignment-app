<?php

declare(strict_types=1);

namespace Tests\Domains\Tenancy\Feature;

use App\Domains\Tenancy\Data\OrgNodeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Domains\Foundation\TestCase;

class TenantRootCompanyBackfillMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function testMigrationBackfillsMissingRootCompanyAndReparentsTopLevelTrees(): void
    {
        $tenantId = $this->insertTenantRecord('Repair Target');
        $departmentId = $this->insertOrgNode($tenantId, null, OrgNodeType::Department->value, 'Engineering', 0);
        $teamId = $this->insertOrgNode($tenantId, $departmentId, OrgNodeType::Team->value, 'Platform', 1);

        $migration = require base_path(
            'domains/Tenancy/database/migrations/2026_04_04_000003_backfill_missing_root_company_nodes.php',
        );
        $migration->up();

        /** @var object{id: int, name: string, depth: int} $rootCompany */
        $rootCompany = DB::selectOne(
            'SELECT id, name, depth
             FROM org_nodes
             WHERE tenant_id = ?
               AND parent_id IS NULL
               AND node_type = ?
             LIMIT 1',
            [$tenantId, OrgNodeType::Company->value],
        );
        $this->assertSame('Repair Target', $rootCompany->name);
        $this->assertSame(0, (int) $rootCompany->depth);

        /** @var object{parent_id: int|null, depth: int} $department */
        $department = DB::selectOne(
            'SELECT parent_id, depth
             FROM org_nodes
             WHERE id = ?
             LIMIT 1',
            [$departmentId],
        );
        $this->assertSame((int) $rootCompany->id, (int) $department->parent_id);
        $this->assertSame(1, (int) $department->depth);

        /** @var object{parent_id: int|null, depth: int} $team */
        $team = DB::selectOne(
            'SELECT parent_id, depth
             FROM org_nodes
             WHERE id = ?
             LIMIT 1',
            [$teamId],
        );
        $this->assertSame($departmentId, (int) $team->parent_id);
        $this->assertSame(2, (int) $team->depth);

        /** @var object{action: string, metadata: string|null} $audit */
        $audit = DB::selectOne(
            'SELECT action, metadata
             FROM tenant_audit_logs
             WHERE tenant_id = ?
             ORDER BY id DESC
             LIMIT 1',
            [$tenantId],
        );
        $this->assertSame('tenant_repaired', $audit->action);
        $this->assertStringContainsString('backfill_missing_root_company', (string) $audit->metadata);
    }

    private function insertTenantRecord(string $name): int
    {
        DB::insert(
            'INSERT INTO tenants (name, status, plan_tier, hierarchy_depth_limit, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$name, 'active', 'enterprise_pilot', 4, now(), now()],
        );

        return (int) DB::getPdo()->lastInsertId();
    }

    private function insertOrgNode(
        int $tenantId,
        ?int $parentId,
        string $nodeType,
        string $name,
        int $depth,
    ): int {
        DB::insert(
            'INSERT INTO org_nodes (tenant_id, parent_id, node_type, name, depth, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$tenantId, $parentId, $nodeType, $name, $depth, true, now(), now()],
        );

        return (int) DB::getPdo()->lastInsertId();
    }
}
