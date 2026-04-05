<?php

declare(strict_types=1);

namespace App\Domains\Skills\Data;

enum SkillImportance: string
{
    case Critical = 'critical';
    case Core = 'core';
    case Supporting = 'supporting';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $importance): string => $importance->value,
            self::cases(),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Critical',
            self::Core => 'Core',
            self::Supporting => 'Supporting',
        };
    }
}
