<?php

declare(strict_types=1);

namespace App\Domains\Skills\Data;

final readonly class RoleMappingVersionData
{
    /**
     * @param list<RoleMappingSkillData> $skills
     */
    public function __construct(
        public int $id,
        public int $versionNumber,
        public ?string $summary,
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
            'id' => $this->id,
            'version_number' => $this->versionNumber,
            'summary' => $this->summary,
            'published_at' => $this->publishedAt,
            'published_by_name' => $this->publishedByName,
            'skills' => array_map(
                static fn (RoleMappingSkillData $skill): array => $skill->toArray(),
                $this->skills,
            ),
        ];
    }
}
