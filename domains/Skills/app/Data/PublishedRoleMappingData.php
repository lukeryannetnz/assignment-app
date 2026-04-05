<?php

declare(strict_types=1);

namespace App\Domains\Skills\Data;

final readonly class PublishedRoleMappingData
{
    /**
     * @param list<RoleMappingSkillData> $skills
     */
    public function __construct(
        public int $roleId,
        public string $roleName,
        public ?string $roleFamily,
        public ?string $roleDescription,
        public int $versionNumber,
        public string $publishedAt,
        public ?string $publishedByName,
        public array $skills,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'role_id' => $this->roleId,
            'role_name' => $this->roleName,
            'role_family' => $this->roleFamily,
            'role_description' => $this->roleDescription,
            'version_number' => $this->versionNumber,
            'published_at' => $this->publishedAt,
            'published_by_name' => $this->publishedByName,
            'skills' => array_map(
                static fn (RoleMappingSkillData $skill): array => $skill->toArray(),
                $this->skills,
            ),
        ];
    }
}
