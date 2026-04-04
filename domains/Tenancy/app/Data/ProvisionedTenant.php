<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Data;

use JsonSerializable;

class ProvisionedTenant implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $status,
        public readonly PlanTier $planTier,
        public readonly int $hierarchyDepthLimit,
    ) {
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     status: string,
     *     plan_tier: string,
     *     hierarchy_depth_limit: int
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'plan_tier' => $this->planTier->value,
            'hierarchy_depth_limit' => $this->hierarchyDepthLimit,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     status: string,
     *     plan_tier: string,
     *     hierarchy_depth_limit: int
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
