<?php

declare(strict_types=1);

namespace Database\Seeders\Foundation;

use App\Domains\Tenancy\Data\PlanTier;
use Database\Seeders\CourseCatalog\CourseSeeder;
use Database\Seeders\Curriculum\CurriculumSeeder;
use Database\Seeders\Skills\SkillsStarterLibrarySeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $existingTenantId = $this->resolveExistingTenantId();

        if ($existingTenantId === null) {
            $tenantId = $this->insertTenantRecord('Default Demo Tenant', 'active', PlanTier::EnterprisePilot->value, 4);
        } else {
            if (!is_numeric($existingTenantId)) {
                throw new \RuntimeException('Unable to resolve a numeric tenant ID for seeding.');
            }

            $tenantId = (int) $existingTenantId;
        }

        $this->callWith(CourseSeeder::class, ['tenantId' => $tenantId]);
        $this->callWith(CurriculumSeeder::class, ['tenantId' => $tenantId]);

        $this->insertUserRecord($tenantId, 'Admin User', 'admin@example.com', true, false);
        $this->insertUserRecord($tenantId, 'Student User', 'student@example.com', false, true);
        $this->insertUserRecord($tenantId, 'Admin Student User', 'both@example.com', true, true);
        $this->insertBulkStudentUsers($tenantId, 5);
        $this->callWith(SkillsStarterLibrarySeeder::class, ['tenantId' => $tenantId]);
    }

    private function resolveExistingTenantId(): int|string|null
    {
        /** @var object{tenant_id: int|string|null}|null $row */
        $row = DB::selectOne(
            'SELECT tenant_id
             FROM users
             WHERE tenant_id IS NOT NULL
             LIMIT 1',
        );

        if ($row !== null && $row->tenant_id !== null) {
            return $row->tenant_id;
        }

        /** @var object{id: int|string}|null $tenantRow */
        $tenantRow = DB::selectOne(
            'SELECT id
             FROM tenants
             LIMIT 1',
        );

        return $tenantRow?->id;
    }

    private function insertTenantRecord(string $name, string $status, string $planTier, int $hierarchyDepthLimit): int
    {
        DB::insert(
            'INSERT INTO tenants (name, status, plan_tier, hierarchy_depth_limit, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$name, $status, $planTier, $hierarchyDepthLimit, now(), now()],
        );

        return (int) DB::getPdo()->lastInsertId();
    }

    private function insertUserRecord(
        int $tenantId,
        string $name,
        string $email,
        bool $isAdmin,
        bool $isStudent,
    ): void {
        DB::insert(
            'INSERT INTO users
                (tenant_id, name, email, email_verified_at, password, remember_token,
                 is_student, is_admin, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $tenantId,
                $name,
                $email,
                now(),
                bcrypt('password'),
                substr(md5($email), 0, 10),
                $isStudent,
                $isAdmin,
                now(),
                now(),
            ],
        );
    }

    private function insertBulkStudentUsers(int $tenantId, int $count): void
    {
        for ($index = 1; $index <= $count; $index++) {
            $email = sprintf('student-%d@example.com', $index);
            $this->insertUserRecord($tenantId, 'Student User ' . $index, $email, false, true);
        }
    }
}
