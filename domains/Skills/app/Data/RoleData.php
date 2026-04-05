<?php

declare(strict_types=1);

namespace App\Domains\Skills\Data;

final readonly class RoleData
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $family,
        public ?string $description,
        public bool $hasPublishedMapping,
        public ?int $currentVersionNumber,
    ) {
    }

    /**
     * @return array<string, int|string|null|bool>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'family' => $this->family,
            'description' => $this->description,
            'has_published_mapping' => $this->hasPublishedMapping,
            'current_version_number' => $this->currentVersionNumber,
        ];
    }
}
