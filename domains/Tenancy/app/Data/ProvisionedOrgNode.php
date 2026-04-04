<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Data;

use JsonSerializable;

class ProvisionedOrgNode implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly int $tenantId,
        public readonly ?int $parentId,
        public readonly OrgNodeType $nodeType,
        public readonly string $name,
        public readonly int $depth,
        public readonly bool $isActive,
    ) {
    }

    /**
     * @return array{
     *     id: int,
     *     tenant_id: int,
     *     parent_id: int|null,
     *     node_type: string,
     *     name: string,
     *     depth: int,
     *     is_active: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'parent_id' => $this->parentId,
            'node_type' => $this->nodeType->value,
            'name' => $this->name,
            'depth' => $this->depth,
            'is_active' => $this->isActive,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     tenant_id: int,
     *     parent_id: int|null,
     *     node_type: string,
     *     name: string,
     *     depth: int,
     *     is_active: bool
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
