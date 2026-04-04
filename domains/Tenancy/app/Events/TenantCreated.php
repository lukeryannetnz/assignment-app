<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Events;

class TenantCreated
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $actorUserId,
        public readonly int $rootOrgNodeId,
    ) {
    }
}
