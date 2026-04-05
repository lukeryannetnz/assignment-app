<?php

declare(strict_types=1);

namespace Tests\Domains\Skills\Feature;

use App\Domains\Skills\Data\ProficiencyBand;
use App\Domains\Skills\Data\SkillImportance;
use App\Domains\Skills\Events\RoleMappingCreated;
use App\Domains\Skills\Events\RoleMappingImported;
use App\Domains\Skills\Events\RoleMappingPublished;
use App\Domains\Skills\Events\RoleMappingUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Domains\Skills\TestCase;

class RoleMappingComponentTest extends TestCase
{
    public function testRoleMappingRoutesRequireAuthenticationAndAdminEditingPrivileges(): void
    {
        $tenantId = $this->insertTenantRecord();
        $manager = $this->createUserRecord($tenantId, false, false, 'manager-role-maps@example.test');

        $this->get('/admin/skills/role-mappings')->assertRedirect('/login');
        $this->actingAs($manager)->get('/admin/skills/role-mappings')->assertForbidden();
        $this->actingAs($manager)->get('/skills/role-mappings')->assertOk();
    }

    public function testAdminCanLoadStarterLibraryCreateDraftAndPublishRoleMapping(): void
    {
        Event::fake();

        $tenantId = $this->insertTenantRecord();
        $admin = $this->createUserRecord($tenantId, true, false, 'skills-admin@example.test');
        $manager = $this->createUserRecord($tenantId, false, false, 'skills-manager@example.test');

        $this->actingAs($admin)
            ->post('/admin/skills/role-mappings/starter-library')
            ->assertRedirect('/admin/skills/role-mappings');

        $starterLibraryResponse = $this->actingAs($admin)->get('/admin/skills/role-mappings');
        $starterLibraryResponse->assertOk();
        $starterLibraryResponse->assertSee('Software Development');
        $starterLibraryResponse->assertSee('Product Management');
        $starterLibraryResponse->assertSee('Product Manager');

        $this->actingAs($admin)->post('/admin/skills/role-mappings/roles', [
            'name' => 'QA Engineer',
            'role_family' => 'Software Development',
            'description' => 'Owns test strategy and release confidence.',
        ])->assertRedirect();

        /** @var object{id: int} $role */
        $role = $this->selectOne(
            'SELECT id
             FROM skill_roles
             WHERE tenant_id = ?
               AND name = ?
             LIMIT 1',
            [$tenantId, 'QA Engineer'],
        );

        $this->actingAs($admin)->post('/admin/skills/role-mappings/skills', [
            'name' => 'Test automation strategy',
            'skill_family' => 'Engineering Quality',
            'description' => 'Designs a maintainable automated test approach.',
            'role' => (int) $role->id,
        ])->assertRedirect();

        /** @var object{id: int} $skill */
        $skill = $this->selectOne(
            'SELECT id
             FROM skill_definitions
             WHERE tenant_id = ?
               AND name = ?
             LIMIT 1',
            [$tenantId, 'Test automation strategy'],
        );

        $this->actingAs($admin)->put("/admin/skills/role-mappings/{$role->id}", [
            'summary' => 'QA mapping focused on release confidence and automated verification.',
            'skills' => [
                [
                    'skill_id' => (int) $skill->id,
                    'importance' => SkillImportance::Critical->value,
                    'target_proficiency' => ProficiencyBand::Advanced->value,
                    'rationale_note' => 'This role keeps regression risk low before release.',
                ],
            ],
        ])->assertRedirect("/admin/skills/role-mappings?role={$role->id}");

        $this->actingAs($admin)
            ->post("/admin/skills/role-mappings/{$role->id}/publish")
            ->assertRedirect("/admin/skills/role-mappings?role={$role->id}");

        $managerResponse = $this->actingAs($manager)->get("/skills/role-mappings?role={$role->id}");
        $managerResponse->assertOk();
        $managerResponse->assertSee('QA Engineer');
        $managerResponse->assertSee('Test automation strategy');
        $managerResponse->assertSee('Advanced');

        /** @var object{aggregate: int|string} $versionCount */
        $versionCount = $this->selectOne(
            'SELECT COUNT(*) AS aggregate
             FROM role_skill_mapping_versions
             WHERE tenant_id = ?
               AND role_id = ?',
            [$tenantId, (int) $role->id],
        );
        $this->assertSame(1, (int) $versionCount->aggregate);

        /** @var object{current_version_id: int|null, draft_summary: string|null} $mapping */
        $mapping = $this->selectOne(
            'SELECT current_version_id, draft_summary
             FROM role_skill_mappings
             WHERE tenant_id = ?
               AND role_id = ?
             LIMIT 1',
            [$tenantId, (int) $role->id],
        );
        $this->assertNotNull($mapping->current_version_id);
        $this->assertSame(
            'QA mapping focused on release confidence and automated verification.',
            $mapping->draft_summary,
        );

        /** @var object{aggregate: int|string} $auditCount */
        $auditCount = $this->selectOne(
            'SELECT COUNT(*) AS aggregate
             FROM tenant_audit_logs
             WHERE tenant_id = ?
               AND action IN (?, ?, ?, ?)',
            [
                $tenantId,
                'role_mapping_created',
                'role_mapping_updated',
                'role_mapping_published',
                'role_mapping_imported',
            ],
        );
        $this->assertGreaterThanOrEqual(4, (int) $auditCount->aggregate);

        Event::assertDispatched(
            RoleMappingImported::class,
            static function (RoleMappingImported $event) use ($tenantId): bool {
                return $event->tenantId === $tenantId
                    && ($event->metadata['source'] ?? null) === 'starter_library';
            },
        );

        Event::assertDispatched(
            RoleMappingCreated::class,
            static function (RoleMappingCreated $event) use ($tenantId, $role): bool {
                return $event->tenantId === $tenantId
                    && $event->roleId === (int) $role->id
                    && ($event->metadata['role_name'] ?? null) === 'QA Engineer';
            },
        );

        Event::assertDispatched(
            RoleMappingUpdated::class,
            static function (RoleMappingUpdated $event) use ($tenantId, $role): bool {
                return $event->tenantId === $tenantId
                    && $event->roleId === (int) $role->id
                    && ($event->metadata['skill_count'] ?? null) === 1;
            },
        );

        Event::assertDispatched(
            RoleMappingPublished::class,
            static function (RoleMappingPublished $event) use ($tenantId, $role): bool {
                return $event->tenantId === $tenantId
                    && $event->roleId === (int) $role->id
                    && ($event->metadata['version_number'] ?? null) === 1;
            },
        );
    }

    /**
     * @param list<int|string> $bindings
     */
    private function selectOne(string $sql, array $bindings): object
    {
        /** @var object|null $row */
        $row = DB::selectOne($sql, $bindings);

        if ($row === null) {
            throw new \RuntimeException('Expected query to return a row.');
        }

        return $row;
    }
}
