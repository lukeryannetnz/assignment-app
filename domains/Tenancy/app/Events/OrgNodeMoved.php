<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Events;

class OrgNodeMoved extends TenancyLifecycleEvent
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly int $orgNodeId,
        int $tenantId,
        ?int $actorUserId,
        array $metadata,
    ) {
        parent::__construct(
            action: 'org_node_moved',
            tenantId: $tenantId,
            actorUserId: $actorUserId,
            entityType: 'org_node',
            entityId: $orgNodeId,
            metadata: $metadata,
        );
    }
}
