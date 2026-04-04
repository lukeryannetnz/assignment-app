<?php

declare(strict_types=1);

namespace Tests\Domains\Tenancy\Feature;

use App\Domains\Tenancy\Data\OrgNodeType;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Domains\Foundation\TestCase;

class TenantConstraintEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function testCannotInsertCourseWithoutTenantId(): void
    {
        $this->expectException(QueryException::class);

        DB::table('courses')->insert([
            'name' => 'No Tenant Course',
            'description' => 'Should fail due to non-null tenant_id.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function testCannotInsertCrossTenantSectionReference(): void
    {
        $tenantAId = $this->insertTenantRecord('Tenant A');
        $tenantBId = $this->insertTenantRecord('Tenant B');
        $courseBId = $this->insertCourseRecord($tenantBId, 'Foreign Course');

        $this->expectException(QueryException::class);

        DB::insert(
            'INSERT INTO sections (tenant_id, course_id, title, `order`, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$tenantAId, $courseBId, 'Cross Tenant Section', 1, now(), now()],
        );
    }

    public function testCannotInsertCrossTenantOrgParentReference(): void
    {
        $tenantAId = $this->insertTenantRecord('Tenant A');
        $tenantBId = $this->insertTenantRecord('Tenant B');

        $parentBId = $this->insertOrgNodeRecord($tenantBId, null, OrgNodeType::Company, 'Tenant B Root', 0, true);

        $this->expectException(QueryException::class);

        DB::insert(
            'INSERT INTO org_nodes (tenant_id, parent_id, node_type, name, depth, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $tenantAId,
                $parentBId,
                OrgNodeType::Department->value,
                'Invalid Cross Tenant Child',
                1,
                true,
                now(),
                now(),
            ],
        );
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

    private function insertCourseRecord(int $tenantId, string $name): int
    {
        DB::insert(
            'INSERT INTO courses (tenant_id, name, description, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?)',
            [$tenantId, $name, 'Foreign course description', now(), now()],
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

    private function lastInsertId(): int
    {
        return (int) DB::getPdo()->lastInsertId();
    }
}
