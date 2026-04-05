<?php

declare(strict_types=1);

namespace App\Domains\Skills\Data;

final readonly class SkillData
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $family,
        public ?string $description,
    ) {
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'family' => $this->family,
            'description' => $this->description,
        ];
    }
}
