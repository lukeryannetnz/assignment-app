<?php

declare(strict_types=1);

namespace App\Domains\Skills\Events;

final readonly class RoleMappingPublished
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public int $tenantId,
        public int $roleId,
        public ?int $actorUserId,
        public array $metadata,
    ) {
    }
}
