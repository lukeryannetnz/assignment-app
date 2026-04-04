<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Data;

enum OrgNodeType: string
{
    case Company = 'company';
    case BusinessUnit = 'business_unit';
    case Department = 'department';
    case Team = 'team';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::cases(),
        );
    }
}
