<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Data;

use JsonSerializable;

class OrganizationHierarchyImportCommitResult implements JsonSerializable
{
    /**
     * @param  list<ProvisionedOrgNode>  $createdNodes
     */
    public function __construct(
        public readonly int $importedCount,
        public readonly array $createdNodes,
    ) {
    }

    /**
     * @return array{
     *     imported_count: int,
     *     created_nodes: list<array{
     *         id: int,
     *         tenant_id: int,
     *         parent_id: int|null,
     *         node_type: string,
     *         name: string,
     *         depth: int,
     *         is_active: bool
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'imported_count' => $this->importedCount,
            'created_nodes' => array_map(
                static fn (ProvisionedOrgNode $node): array => $node->toArray(),
                $this->createdNodes,
            ),
        ];
    }

    /**
     * @return array{
     *     imported_count: int,
     *     created_nodes: list<array{
     *         id: int,
     *         tenant_id: int,
     *         parent_id: int|null,
     *         node_type: string,
     *         name: string,
     *         depth: int,
     *         is_active: bool
     *     }>
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
