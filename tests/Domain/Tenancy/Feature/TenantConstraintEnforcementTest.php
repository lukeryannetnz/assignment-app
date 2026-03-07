<?php

declare(strict_types=1);

namespace Tests\Domain\Tenancy\Feature;

use App\Domain\CourseCatalog\Models\Course;
use App\Domain\Curriculum\Models\Section;
use App\Domain\Tenancy\Models\OrgNode;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Foundation\TestCase;

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
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $courseB = Course::factory()->create(['tenant_id' => $tenantB->id]);

        $this->expectException(QueryException::class);

        Section::query()->create([
            'tenant_id' => $tenantA->id,
            'course_id' => $courseB->id,
            'title' => 'Cross Tenant Section',
            'order' => 1,
        ]);
    }

    public function testCannotInsertCrossTenantOrgParentReference(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $parentB = OrgNode::factory()->create([
            'tenant_id' => $tenantB->id,
            'node_type' => 'company',
            'parent_id' => null,
            'depth' => 0,
        ]);

        $this->expectException(QueryException::class);

        OrgNode::query()->create([
            'tenant_id' => $tenantA->id,
            'parent_id' => $parentB->id,
            'node_type' => 'department',
            'name' => 'Invalid Cross Tenant Child',
            'depth' => 1,
            'is_active' => true,
        ]);
    }
}
