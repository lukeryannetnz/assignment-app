<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Data;

use JsonSerializable;

class ProvisioningResult implements JsonSerializable
{
    public function __construct(
        public readonly ProvisionedTenant $tenant,
        public readonly ProvisionedOrgNode $rootOrgNode,
    ) {
    }

    /**
     * @return array{
     *     tenant: array{
     *         id: int,
     *         name: string,
     *         status: string,
     *         plan_tier: string,
     *         hierarchy_depth_limit: int
     *     },
     *     root_org_node: array{
     *         id: int,
     *         tenant_id: int,
     *         parent_id: int|null,
     *         node_type: string,
     *         name: string,
     *         depth: int,
     *         is_active: bool
     *     }
     * }
     */
    public function toArray(): array
    {
        return [
            'tenant' => $this->tenant->toArray(),
            'root_org_node' => $this->rootOrgNode->toArray(),
        ];
    }

    /**
     * @return array{
     *     tenant: array{
     *         id: int,
     *         name: string,
     *         status: string,
     *         plan_tier: string,
     *         hierarchy_depth_limit: int
     *     },
     *     root_org_node: array{
     *         id: int,
     *         tenant_id: int,
     *         parent_id: int|null,
     *         node_type: string,
     *         name: string,
     *         depth: int,
     *         is_active: bool
     *     }
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
