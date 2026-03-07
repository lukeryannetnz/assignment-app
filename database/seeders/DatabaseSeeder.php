<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\IdentityAccess\Models\User;
use Database\Seeders\CourseCatalog\CourseSeeder;
use Database\Seeders\Curriculum\CurriculumSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $existingTenantId = User::query()
            ->whereNotNull('tenant_id')
            ->value('tenant_id');
        if ($existingTenantId === null) {
            $existingTenantId = Tenant::query()->value('id');
        }

        if ($existingTenantId === null) {
            $tenant = Tenant::factory()->create([
                'name' => 'Default Demo Tenant',
                'status' => 'active',
                'plan_tier' => 'enterprise_pilot',
                'hierarchy_depth_limit' => 4,
            ]);
        } else {
            if (!is_numeric($existingTenantId)) {
                throw new \RuntimeException('Unable to resolve a numeric tenant ID for seeding.');
            }

            $tenant = Tenant::query()->findOrFail((int) $existingTenantId);
        }

        $this->callWith(CourseSeeder::class, ['tenantId' => $tenant->id]);
        $this->callWith(CurriculumSeeder::class, ['tenantId' => $tenant->id]);

        // Create an admin user
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_admin' => true,
            'is_student' => false,
        ]);

        // Create a student user
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Student User',
            'email' => 'student@example.com',
            'is_admin' => false,
            'is_student' => true,
        ]);

        // Create a user with both roles
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Student User',
            'email' => 'both@example.com',
            'is_admin' => true,
            'is_student' => true,
        ]);

        // Create additional students
        User::factory()->count(5)->create([
            'tenant_id' => $tenant->id,
            'is_admin' => false,
            'is_student' => true,
        ]);
    }
}
