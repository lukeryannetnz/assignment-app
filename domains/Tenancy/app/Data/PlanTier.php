<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Data;

enum PlanTier: string
{
    case EnterprisePilot = 'enterprise_pilot';
    case Enterprise = 'enterprise';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $tier): string => $tier->value,
            self::cases(),
        );
    }
}
