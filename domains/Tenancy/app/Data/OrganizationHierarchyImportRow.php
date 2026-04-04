<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Data;

use JsonSerializable;

class OrganizationHierarchyImportRow implements JsonSerializable
{
    public function __construct(
        public readonly int $rowNumber,
        public readonly string $rowKey,
        public readonly ?string $parentRowKey,
        public readonly ?OrgNodeType $nodeType,
        public readonly string $name,
        public readonly ?int $resolvedDepth,
    ) {
    }

    /**
     * @return array{
     *     row_number: int,
     *     row_key: string,
     *     parent_row_key: string|null,
     *     node_type: string|null,
     *     name: string,
     *     resolved_depth: int|null
     * }
     */
    public function toArray(): array
    {
        return [
            'row_number' => $this->rowNumber,
            'row_key' => $this->rowKey,
            'parent_row_key' => $this->parentRowKey,
            'node_type' => $this->nodeType?->value,
            'name' => $this->name,
            'resolved_depth' => $this->resolvedDepth,
        ];
    }

    /**
     * @return array{
     *     row_number: int,
     *     row_key: string,
     *     parent_row_key: string|null,
     *     node_type: string|null,
     *     name: string,
     *     resolved_depth: int|null
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
