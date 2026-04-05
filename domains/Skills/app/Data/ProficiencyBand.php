<?php

declare(strict_types=1);

namespace App\Domains\Skills\Data;

enum ProficiencyBand: string
{
    case Foundational = 'foundational';
    case Proficient = 'proficient';
    case Advanced = 'advanced';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $band): string => $band->value,
            self::cases(),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::Foundational => 'Foundational',
            self::Proficient => 'Proficient',
            self::Advanced => 'Advanced',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Foundational => 'Learner can contribute with guidance and structured support.',
            self::Proficient => 'Learner can work independently across normal scenarios.',
            self::Advanced => 'Learner can lead, coach, and set standards for others.',
        };
    }
}
