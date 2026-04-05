<?php

declare(strict_types=1);

namespace App\Domains\Skills\Services;

use App\Domains\Skills\Data\ProficiencyBand;
use App\Domains\Skills\Data\PublishedRoleMappingData;
use App\Domains\Skills\Data\RoleData;
use App\Domains\Skills\Data\RoleMappingSkillData;
use App\Domains\Skills\Data\RoleMappingVersionData;
use App\Domains\Skills\Data\RoleMappingWorkspaceData;
use App\Domains\Skills\Data\SkillData;
use App\Domains\Skills\Data\SkillImportance;
use App\Domains\Tenancy\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class RoleMappingService
{
    private const ROLE_TABLE = 'skill_roles';
    private const SKILL_TABLE = 'skill_definitions';

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly RoleMappingTelemetryService $telemetryService,
        private readonly SkillsStarterLibrary $starterLibrary,
    ) {
    }

    public function buildWorkspace(?int $selectedRoleId = null): RoleMappingWorkspaceData
    {
        $roles = $this->listRoles();
        $skills = $this->listSkills();
        $selectedRole = $selectedRoleId !== null ? $this->findRoleByIdOrNull($selectedRoleId) : $roles[0] ?? null;
        $selectedRoleId = $selectedRole?->id;

        return new RoleMappingWorkspaceData(
            roles: $roles,
            skills: $skills,
            draftSkills: $selectedRoleId !== null ? $this->listDraftSkills($selectedRoleId) : [],
            starterFamilies: $this->starterLibrarySummary(),
            publishedCount: $this->publishedCount(),
            selectedRoleId: $selectedRoleId,
            selectedRoleName: $selectedRole?->name,
            selectedRoleFamily: $selectedRole?->family,
            selectedRoleDescription: $selectedRole?->description,
            draftSummary: $selectedRoleId !== null ? $this->draftSummary($selectedRoleId) : null,
            publishedVersion: $selectedRoleId !== null ? $this->currentPublishedVersion($selectedRoleId) : null,
        );
    }

    /**
     * @return list<RoleData>
     */
    public function listRoles(): array
    {
        $tenantId = $this->tenantContext->requireTenantId();

        /** @var list<object{
         *     id: int,
         *     name: string,
         *     role_family: string|null,
         *     description: string|null,
         *     current_version_id: int|null
         * }> $rows
         */
        $rows = DB::select(
            'SELECT r.id, r.name, r.role_family, r.description, m.current_version_id
             FROM ' . self::ROLE_TABLE . ' r
             LEFT JOIN role_skill_mappings m
               ON m.tenant_id = r.tenant_id
              AND m.role_id = r.id
             WHERE r.tenant_id = ?
             ORDER BY r.role_family IS NULL, r.role_family ASC, r.name ASC',
            [$tenantId],
        );

        return array_map(
            static fn (object $row): RoleData => new RoleData(
                id: (int) $row->id,
                name: (string) $row->name,
                family: $row->role_family !== null ? (string) $row->role_family : null,
                description: $row->description !== null ? (string) $row->description : null,
                hasPublishedMapping: $row->current_version_id !== null,
                currentVersionNumber: null,
            ),
            $rows,
        );
    }

    /**
     * @return list<SkillData>
     */
    public function listSkills(): array
    {
        $tenantId = $this->tenantContext->requireTenantId();

        /** @var list<object{id: int, name: string, skill_family: string|null, description: string|null}> $rows */
        $rows = DB::select(
            'SELECT id, name, skill_family, description
             FROM ' . self::SKILL_TABLE . '
             WHERE tenant_id = ?
             ORDER BY skill_family IS NULL, skill_family ASC, name ASC',
            [$tenantId],
        );

        return array_map(
            static fn (object $row): SkillData => new SkillData(
                id: (int) $row->id,
                name: (string) $row->name,
                family: $row->skill_family !== null ? (string) $row->skill_family : null,
                description: $row->description !== null ? (string) $row->description : null,
            ),
            $rows,
        );
    }

    public function createRole(string $name, ?string $roleFamily, ?string $description, int $actorUserId): int
    {
        $tenantId = $this->tenantContext->requireTenantId();
        $trimmedName = trim($name);

        if ($trimmedName === '') {
            throw new \InvalidArgumentException('Role name is required.');
        }

        return DB::transaction(function () use ($tenantId, $trimmedName, $roleFamily, $description, $actorUserId): int {
            /** @var object{id: int}|null $existing */
            $existing = DB::selectOne(
                'SELECT id
                 FROM ' . self::ROLE_TABLE . '
                 WHERE tenant_id = ?
                   AND name = ?
                 LIMIT 1',
                [$tenantId, $trimmedName],
            );

            if ($existing !== null) {
                return (int) $existing->id;
            }

            DB::insert(
                'INSERT INTO ' . self::ROLE_TABLE . ' (tenant_id, name, role_family, description, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $tenantId,
                    $trimmedName,
                    $this->normalizeNullable($roleFamily),
                    $this->normalizeNullable($description),
                    now(),
                    now(),
                ],
            );

            $roleId = (int) DB::getPdo()->lastInsertId();
            $this->ensureMappingShell($roleId);
            $this->telemetryService->recordCreated($tenantId, $roleId, $actorUserId, [
                'role_name' => $trimmedName,
                'role_family' => $this->normalizeNullable($roleFamily),
            ]);

            return $roleId;
        });
    }

    public function createSkill(string $name, ?string $skillFamily, ?string $description): int
    {
        $tenantId = $this->tenantContext->requireTenantId();
        $trimmedName = trim($name);

        if ($trimmedName === '') {
            throw new \InvalidArgumentException('Skill name is required.');
        }

        /** @var object{id: int}|null $existing */
        $existing = DB::selectOne(
            'SELECT id
             FROM ' . self::SKILL_TABLE . '
             WHERE tenant_id = ?
               AND name = ?
             LIMIT 1',
            [$tenantId, $trimmedName],
        );

        if ($existing !== null) {
            return (int) $existing->id;
        }

        DB::insert(
            'INSERT INTO ' . self::SKILL_TABLE . ' (tenant_id, name, skill_family, description, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $tenantId,
                $trimmedName,
                $this->normalizeNullable($skillFamily),
                $this->normalizeNullable($description),
                now(),
                now(),
            ],
        );

        return (int) DB::getPdo()->lastInsertId();
    }

    /**
     * @param list<array{
     *     skill_id: int,
     *     importance: SkillImportance,
     *     target_proficiency: ProficiencyBand,
     *     rationale_note: string|null
     * }> $rows
     */
    public function saveDraft(int $roleId, ?string $summary, array $rows, int $actorUserId): void
    {
        $tenantId = $this->tenantContext->requireTenantId();
        $this->findRoleById($roleId);
        $mappingId = $this->ensureMappingShell($roleId);
        $validatedRows = $this->validateDraftRows($rows);

        DB::transaction(function () use ($tenantId, $roleId, $mappingId, $summary, $validatedRows, $actorUserId): void {
            DB::update(
                'UPDATE role_skill_mappings
                 SET draft_summary = ?, updated_at = ?
                 WHERE tenant_id = ?
                   AND id = ?',
                [$this->normalizeNullable($summary), now(), $tenantId, $mappingId],
            );

            DB::delete(
                'DELETE FROM role_skill_mapping_skills
                 WHERE tenant_id = ?
                   AND role_skill_mapping_id = ?',
                [$tenantId, $mappingId],
            );

            foreach ($validatedRows as $index => $row) {
                DB::insert(
                    'INSERT INTO role_skill_mapping_skills
                        (tenant_id, role_skill_mapping_id, skill_id, importance,
                         target_proficiency, rationale_note, sort_order, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $tenantId,
                        $mappingId,
                        $row['skill_id'],
                        $row['importance']->value,
                        $row['target_proficiency']->value,
                        $this->normalizeNullable($row['rationale_note']),
                        $index + 1,
                        now(),
                        now(),
                    ],
                );
            }

            $this->telemetryService->recordUpdated($tenantId, $roleId, $actorUserId, [
                'skill_count' => count($validatedRows),
                'summary_present' => $this->normalizeNullable($summary) !== null,
            ]);
        });
    }

    public function publish(int $roleId, int $actorUserId): RoleMappingVersionData
    {
        $tenantId = $this->tenantContext->requireTenantId();
        $this->findRoleById($roleId);
        $mappingId = $this->ensureMappingShell($roleId);
        $draftSkills = $this->listDraftSkills($roleId);

        if ($draftSkills === []) {
            throw new \InvalidArgumentException('At least one mapped skill is required before publish.');
        }

        $hasCoreOrCritical = count(array_filter(
            $draftSkills,
            static fn (RoleMappingSkillData $skill): bool => in_array(
                $skill->importance,
                [SkillImportance::Critical, SkillImportance::Core],
                true,
            ),
        )) > 0;

        if (!$hasCoreOrCritical) {
            throw new \InvalidArgumentException('At least one critical or core skill is required before publish.');
        }

        return DB::transaction(function () use (
            $tenantId,
            $roleId,
            $mappingId,
            $draftSkills,
            $actorUserId,
        ): RoleMappingVersionData {
            /** @var object{version_number: int|null}|null $row */
            $row = DB::selectOne(
                'SELECT MAX(version_number) AS version_number
                 FROM role_skill_mapping_versions
                 WHERE tenant_id = ?
                   AND role_id = ?',
                [$tenantId, $roleId],
            );

            $versionNumber = ((int) ($row->version_number ?? 0)) + 1;
            $summary = $this->draftSummary($roleId);

            DB::insert(
                'INSERT INTO role_skill_mapping_versions
                    (tenant_id, role_id, role_skill_mapping_id, version_number,
                     summary, published_at, published_by_user_id, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $tenantId,
                    $roleId,
                    $mappingId,
                    $versionNumber,
                    $summary,
                    now(),
                    $actorUserId,
                    now(),
                    now(),
                ],
            );

            $versionId = (int) DB::getPdo()->lastInsertId();

            foreach ($draftSkills as $skill) {
                DB::insert(
                    'INSERT INTO role_skill_mapping_version_skills
                        (tenant_id, role_skill_mapping_version_id, skill_id, importance,
                         target_proficiency, rationale_note, sort_order, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $tenantId,
                        $versionId,
                        $skill->skillId,
                        $skill->importance->value,
                        $skill->targetProficiency->value,
                        $this->normalizeNullable($skill->rationaleNote),
                        $skill->sortOrder,
                        now(),
                        now(),
                    ],
                );
            }

            DB::update(
                'UPDATE role_skill_mappings
                 SET current_version_id = ?, published_at = ?, published_by_user_id = ?, updated_at = ?
                 WHERE tenant_id = ?
                   AND id = ?',
                [$versionId, now(), $actorUserId, now(), $tenantId, $mappingId],
            );

            $this->telemetryService->recordPublished($tenantId, $roleId, $actorUserId, [
                'version_number' => $versionNumber,
                'skill_count' => count($draftSkills),
            ]);

            $version = $this->currentPublishedVersion($roleId);
            if ($version === null) {
                throw new \RuntimeException('Published version could not be loaded after publish.');
            }

            return $version;
        });
    }

    public function seedStarterLibrary(int $actorUserId): void
    {
        $tenantId = $this->tenantContext->requireTenantId();

        DB::transaction(function () use ($tenantId, $actorUserId): void {
            $importedRoleCount = 0;

            /** @var list<array{
             *     family: string,
             *     description: string,
             *     roles: list<array{
             *         name: string,
             *         description: string,
             *         skills: list<array{
             *             name: string,
             *             family: string,
             *             importance: SkillImportance,
             *             target_proficiency: ProficiencyBand,
             *             rationale_note: string,
             *             sort_order: int
             *         }>
             *     }>
             * }> $starterFamilies
             */
            $starterFamilies = $this->starterLibrary->families();

            foreach ($starterFamilies as $family) {
                foreach ($family['roles'] as $roleTemplate) {
                    $roleId = $this->createRole(
                        $roleTemplate['name'],
                        $family['family'],
                        $roleTemplate['description'],
                        $actorUserId,
                    );

                    $rows = [];
                    foreach ($roleTemplate['skills'] as $skillTemplate) {
                        $skillId = $this->createSkill(
                            $skillTemplate['name'],
                            $skillTemplate['family'],
                            $skillTemplate['rationale_note'],
                        );

                        $rows[] = [
                            'skill_id' => $skillId,
                            'importance' => $skillTemplate['importance'],
                            'target_proficiency' => $skillTemplate['target_proficiency'],
                            'rationale_note' => $skillTemplate['rationale_note'],
                        ];
                    }

                    $this->saveDraft($roleId, $roleTemplate['description'], $rows, $actorUserId);

                    if ($this->currentPublishedVersion($roleId) === null) {
                        $this->publish($roleId, $actorUserId);
                    }

                    $importedRoleCount++;
                }
            }

            $this->telemetryService->recordImported($tenantId, $actorUserId, [
                'source' => 'starter_library',
                'role_count' => $importedRoleCount,
                'families' => ['Software Development', 'Product Management'],
            ]);
        });
    }

    /**
     * @return list<PublishedRoleMappingData>
     */
    public function listPublishedMappings(): array
    {
        $tenantId = $this->tenantContext->requireTenantId();

        /** @var list<object{
         *     role_id: int,
         *     role_name: string,
         *     role_family: string|null,
         *     role_description: string|null,
         *     version_number: int,
         *     published_at: string,
         *     published_by_name: string|null
         * }> $rows
         */
        $rows = DB::select(
            'SELECT r.id AS role_id,
                    r.name AS role_name,
                    r.role_family,
                    r.description AS role_description,
                    v.version_number,
                    v.published_at,
                    u.name AS published_by_name
             FROM role_skill_mappings m
             INNER JOIN ' . self::ROLE_TABLE . ' r
               ON r.tenant_id = m.tenant_id
              AND r.id = m.role_id
             INNER JOIN role_skill_mapping_versions v
               ON v.tenant_id = m.tenant_id
              AND v.id = m.current_version_id
             LEFT JOIN users u
               ON u.id = v.published_by_user_id
             WHERE m.tenant_id = ?
               AND m.current_version_id IS NOT NULL
             ORDER BY r.role_family IS NULL, r.role_family ASC, r.name ASC',
            [$tenantId],
        );

        return array_map(function (object $row): PublishedRoleMappingData {
            $roleId = (int) $row->role_id;

            return new PublishedRoleMappingData(
                roleId: $roleId,
                roleName: (string) $row->role_name,
                roleFamily: $row->role_family !== null ? (string) $row->role_family : null,
                roleDescription: $row->role_description !== null ? (string) $row->role_description : null,
                versionNumber: (int) $row->version_number,
                publishedAt: (string) $row->published_at,
                publishedByName: $row->published_by_name !== null ? (string) $row->published_by_name : null,
                skills: $this->listPublishedVersionSkills($roleId),
            );
        }, $rows);
    }

    public function publishedMapping(?int $roleId = null): ?PublishedRoleMappingData
    {
        $publishedMappings = $this->listPublishedMappings();
        if ($publishedMappings === []) {
            return null;
        }

        if ($roleId === null) {
            return $publishedMappings[0];
        }

        foreach ($publishedMappings as $mapping) {
            if ($mapping->roleId === $roleId) {
                return $mapping;
            }
        }

        return null;
    }

    /**
     * @return list<RoleMappingSkillData>
     */
    public function listDraftSkills(int $roleId): array
    {
        $tenantId = $this->tenantContext->requireTenantId();

        /** @var list<object{
         *     skill_id: int,
         *     skill_name: string,
         *     importance: string,
         *     target_proficiency: string,
         *     rationale_note: string|null,
         *     sort_order: int
         * }> $rows
         */
        $rows = DB::select(
            'SELECT s.id AS skill_id,
                    s.name AS skill_name,
                    rs.importance,
                    rs.target_proficiency,
                    rs.rationale_note,
                    rs.sort_order
             FROM role_skill_mappings m
             INNER JOIN role_skill_mapping_skills rs
               ON rs.tenant_id = m.tenant_id
              AND rs.role_skill_mapping_id = m.id
             INNER JOIN ' . self::SKILL_TABLE . ' s
               ON s.tenant_id = rs.tenant_id
              AND s.id = rs.skill_id
             WHERE m.tenant_id = ?
               AND m.role_id = ?
             ORDER BY rs.sort_order ASC, s.name ASC',
            [$tenantId, $roleId],
        );

        return array_map(
            static fn (object $row): RoleMappingSkillData => new RoleMappingSkillData(
                skillId: (int) $row->skill_id,
                skillName: (string) $row->skill_name,
                importance: SkillImportance::from((string) $row->importance),
                targetProficiency: ProficiencyBand::from((string) $row->target_proficiency),
                rationaleNote: $row->rationale_note !== null ? (string) $row->rationale_note : null,
                sortOrder: (int) $row->sort_order,
            ),
            $rows,
        );
    }

    public function currentPublishedVersion(int $roleId): ?RoleMappingVersionData
    {
        $tenantId = $this->tenantContext->requireTenantId();

        /** @var object{
         *     id: int,
         *     version_number: int,
         *     summary: string|null,
         *     published_at: string,
         *     published_by_name: string|null
         * }|null $row
         */
        $row = DB::selectOne(
            'SELECT v.id, v.version_number, v.summary, v.published_at, u.name AS published_by_name
             FROM role_skill_mappings m
             INNER JOIN role_skill_mapping_versions v
               ON v.tenant_id = m.tenant_id
              AND v.id = m.current_version_id
             LEFT JOIN users u
               ON u.id = v.published_by_user_id
             WHERE m.tenant_id = ?
               AND m.role_id = ?
             LIMIT 1',
            [$tenantId, $roleId],
        );

        if ($row === null) {
            return null;
        }

        return new RoleMappingVersionData(
            id: (int) $row->id,
            versionNumber: (int) $row->version_number,
            summary: $row->summary !== null ? (string) $row->summary : null,
            publishedAt: (string) $row->published_at,
            publishedByName: $row->published_by_name !== null ? (string) $row->published_by_name : null,
            skills: $this->listPublishedVersionSkills($roleId),
        );
    }

    public function draftSummary(int $roleId): ?string
    {
        $tenantId = $this->tenantContext->requireTenantId();

        /** @var object{draft_summary: string|null}|null $row */
        $row = DB::selectOne(
            'SELECT draft_summary
             FROM role_skill_mappings
             WHERE tenant_id = ?
               AND role_id = ?
             LIMIT 1',
            [$tenantId, $roleId],
        );

        return $row?->draft_summary !== null ? (string) $row->draft_summary : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function starterLibrarySummary(): array
    {
        $publishedByRoleName = [];
        foreach ($this->listPublishedMappings() as $mapping) {
            $publishedByRoleName[$mapping->roleName] = $mapping;
        }

        $summary = [];
        /** @var list<array{
         *     family: string,
         *     description: string,
         *     roles: list<array{
         *         name: string,
         *         description: string,
         *         skills: list<array{
         *             name: string,
         *             family: string,
         *             importance: SkillImportance,
         *             target_proficiency: ProficiencyBand,
         *             rationale_note: string,
         *             sort_order: int
         *         }>
         *     }>
         * }> $starterFamilies
         */
        $starterFamilies = $this->starterLibrary->families();

        foreach ($starterFamilies as $family) {
            $roles = [];

            foreach ($family['roles'] as $roleTemplate) {
                $published = $publishedByRoleName[$roleTemplate['name']] ?? null;
                $roles[] = [
                    'name' => $roleTemplate['name'],
                    'description' => $roleTemplate['description'],
                    'status' => $published !== null ? 'Published' : 'Not loaded',
                    'published_version' => $published !== null ? 'v' . $published->versionNumber : 'Not published',
                    'skills' => array_map(
                        static function (array $skill): array {
                            return [
                                'name' => (string) $skill['name'],
                                'importance' => $skill['importance']->value,
                                'target_proficiency' => $skill['target_proficiency']->label(),
                            ];
                        },
                        $roleTemplate['skills'],
                    ),
                ];
            }

            $summary[] = [
                'family' => $family['family'],
                'description' => $family['description'],
                'roles' => $roles,
            ];
        }

        return $summary;
    }

    /**
     * @return list<RoleMappingSkillData>
     */
    private function listPublishedVersionSkills(int $roleId): array
    {
        $tenantId = $this->tenantContext->requireTenantId();

        /** @var list<object{
         *     skill_id: int,
         *     skill_name: string,
         *     importance: string,
         *     target_proficiency: string,
         *     rationale_note: string|null,
         *     sort_order: int
         * }> $rows
         */
        $rows = DB::select(
            'SELECT s.id AS skill_id,
                    s.name AS skill_name,
                    vs.importance,
                    vs.target_proficiency,
                    vs.rationale_note,
                    vs.sort_order
             FROM role_skill_mappings m
             INNER JOIN role_skill_mapping_version_skills vs
               ON vs.tenant_id = m.tenant_id
              AND vs.role_skill_mapping_version_id = m.current_version_id
             INNER JOIN ' . self::SKILL_TABLE . ' s
               ON s.tenant_id = vs.tenant_id
              AND s.id = vs.skill_id
             WHERE m.tenant_id = ?
               AND m.role_id = ?
             ORDER BY vs.sort_order ASC, s.name ASC',
            [$tenantId, $roleId],
        );

        return array_map(
            static fn (object $row): RoleMappingSkillData => new RoleMappingSkillData(
                skillId: (int) $row->skill_id,
                skillName: (string) $row->skill_name,
                importance: SkillImportance::from((string) $row->importance),
                targetProficiency: ProficiencyBand::from((string) $row->target_proficiency),
                rationaleNote: $row->rationale_note !== null ? (string) $row->rationale_note : null,
                sortOrder: (int) $row->sort_order,
            ),
            $rows,
        );
    }

    private function ensureMappingShell(int $roleId): int
    {
        $tenantId = $this->tenantContext->requireTenantId();

        /** @var object{id: int}|null $existing */
        $existing = DB::selectOne(
            'SELECT id
             FROM role_skill_mappings
             WHERE tenant_id = ?
               AND role_id = ?
             LIMIT 1',
            [$tenantId, $roleId],
        );

        if ($existing !== null) {
            return (int) $existing->id;
        }

            DB::insert(
                'INSERT INTO role_skill_mappings
                (tenant_id, role_id, draft_summary, current_version_id, published_at,
                 published_by_user_id, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$tenantId, $roleId, null, null, null, null, now(), now()],
            );

        return (int) DB::getPdo()->lastInsertId();
    }

    private function findRoleById(int $roleId): RoleData
    {
        $role = $this->findRoleByIdOrNull($roleId);

        if ($role === null) {
            throw new \InvalidArgumentException('Role was not found in the current tenant.');
        }

        return $role;
    }

    private function findRoleByIdOrNull(int $roleId): ?RoleData
    {
        $tenantId = $this->tenantContext->requireTenantId();

        /** @var object{
         *     id: int,
         *     name: string,
         *     role_family: string|null,
         *     description: string|null,
         *     current_version_id: int|null
         * }|null $row
         */
        $row = DB::selectOne(
            'SELECT r.id, r.name, r.role_family, r.description, m.current_version_id
             FROM ' . self::ROLE_TABLE . ' r
             LEFT JOIN role_skill_mappings m
               ON m.tenant_id = r.tenant_id
              AND m.role_id = r.id
             WHERE r.tenant_id = ?
               AND r.id = ?
             LIMIT 1',
            [$tenantId, $roleId],
        );

        return $row !== null
            ? new RoleData(
                id: (int) $row->id,
                name: (string) $row->name,
                family: $row->role_family !== null ? (string) $row->role_family : null,
                description: $row->description !== null ? (string) $row->description : null,
                hasPublishedMapping: $row->current_version_id !== null,
                currentVersionNumber: null,
            )
            : null;
    }

    private function publishedCount(): int
    {
        $tenantId = $this->tenantContext->requireTenantId();

        /** @var object{aggregate: int|string}|null $row */
        $row = DB::selectOne(
            'SELECT COUNT(*) AS aggregate
             FROM role_skill_mappings
             WHERE tenant_id = ?
               AND current_version_id IS NOT NULL',
            [$tenantId],
        );

        return (int) ($row->aggregate ?? 0);
    }

    /**
     * @param list<array{
     *     skill_id: int,
     *     importance: SkillImportance,
     *     target_proficiency: ProficiencyBand,
     *     rationale_note: string|null
     * }> $rows
     * @return list<array{
     *     skill_id: int,
     *     importance: SkillImportance,
     *     target_proficiency: ProficiencyBand,
     *     rationale_note: string|null
     * }>
     */
    private function validateDraftRows(array $rows): array
    {
        if ($rows === []) {
            throw new \InvalidArgumentException('At least one mapped skill is required.');
        }

        $tenantId = $this->tenantContext->requireTenantId();
        $skillIds = [];
        foreach ($rows as $row) {
            if (in_array($row['skill_id'], $skillIds, true)) {
                throw new \InvalidArgumentException('Duplicate skills are not allowed within a role mapping.');
            }

            $skillIds[] = $row['skill_id'];
        }

        /** @var list<object{id: int}> $existingRows */
        $existingRows = DB::select(
            'SELECT id
             FROM ' . self::SKILL_TABLE . '
             WHERE tenant_id = ?
               AND id IN (' . implode(', ', array_fill(0, count($skillIds), '?')) . ')',
            array_merge([$tenantId], $skillIds),
        );

        if (count($existingRows) !== count($skillIds)) {
            throw new \InvalidArgumentException('One or more selected skills do not belong to the current tenant.');
        }

        return $rows;
    }

    private function normalizeNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
