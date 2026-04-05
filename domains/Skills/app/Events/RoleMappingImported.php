<?php

declare(strict_types=1);

namespace App\Domains\Skills\Events;

final readonly class RoleMappingImported
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public int $tenantId,
        public ?int $actorUserId,
        public array $metadata,
    ) {
    }
}
