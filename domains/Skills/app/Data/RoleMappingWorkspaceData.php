<?php

declare(strict_types=1);

namespace App\Domains\Skills\Data;

final readonly class RoleMappingWorkspaceData
{
    /**
     * @param list<RoleData> $roles
     * @param list<SkillData> $skills
     * @param list<RoleMappingSkillData> $draftSkills
     * @param list<array<string, mixed>> $starterFamilies
     */
    public function __construct(
        public array $roles,
        public array $skills,
        public array $draftSkills,
        public array $starterFamilies,
        public int $publishedCount,
        public ?int $selectedRoleId,
        public ?string $selectedRoleName,
        public ?string $selectedRoleFamily,
        public ?string $selectedRoleDescription,
        public ?string $draftSummary,
        public ?RoleMappingVersionData $publishedVersion,
    ) {
    }
}
