<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Data;

use JsonSerializable;

class ScopeNode implements JsonSerializable
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
     * @param object{
     *     id: int,
     *     tenant_id: int,
     *     parent_id: int|null,
     *     node_type: string,
     *     name: string,
     *     depth: int,
     *     is_active: int
     * } $row
     */
    public static function fromRow(object $row): self
    {
        return new self(
            id: (int) $row->id,
            tenantId: (int) $row->tenant_id,
            parentId: $row->parent_id !== null ? (int) $row->parent_id : null,
            nodeType: OrgNodeType::from((string) $row->node_type),
            name: (string) $row->name,
            depth: (int) $row->depth,
            isActive: (bool) $row->is_active,
        );
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
