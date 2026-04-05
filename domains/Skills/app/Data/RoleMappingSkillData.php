<?php

declare(strict_types=1);

namespace App\Domains\Skills\Data;

final readonly class RoleMappingSkillData
{
    public function __construct(
        public int $skillId,
        public string $skillName,
        public SkillImportance $importance,
        public ProficiencyBand $targetProficiency,
        public ?string $rationaleNote,
        public int $sortOrder,
    ) {
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'skill_id' => $this->skillId,
            'skill_name' => $this->skillName,
            'importance' => $this->importance->value,
            'importance_label' => $this->importance->label(),
            'target_proficiency' => $this->targetProficiency->value,
            'target_proficiency_label' => $this->targetProficiency->label(),
            'rationale_note' => $this->rationaleNote,
            'sort_order' => $this->sortOrder,
        ];
    }
}
