<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Events;

abstract class TenancyLifecycleEvent
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string $action,
        public readonly int $tenantId,
        public readonly ?int $actorUserId,
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly array $metadata,
    ) {
    }
}
