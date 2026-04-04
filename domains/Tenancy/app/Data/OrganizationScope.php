<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Data;

use JsonSerializable;

class OrganizationScope implements JsonSerializable
{
    /**
     * @param list<ScopeNode> $ancestors
     * @param list<ScopeNode> $descendantSubtree
     * @param list<int> $descendantIds
     */
    public function __construct(
        public readonly ScopeNode $node,
        public readonly array $ancestors,
        public readonly array $descendantSubtree,
        public readonly array $descendantIds,
    ) {
    }

    /**
     * @return array{
     *     node: array{
     *         id: int,
     *         tenant_id: int,
     *         parent_id: int|null,
     *         node_type: string,
     *         name: string,
     *         depth: int,
     *         is_active: bool
     *     },
     *     ancestors: list<array{
     *         id: int,
     *         tenant_id: int,
     *         parent_id: int|null,
     *         node_type: string,
     *         name: string,
     *         depth: int,
     *         is_active: bool
     *     }>,
     *     descendant_subtree: list<array{
     *         id: int,
     *         tenant_id: int,
     *         parent_id: int|null,
     *         node_type: string,
     *         name: string,
     *         depth: int,
     *         is_active: bool
     *     }>,
     *     descendant_ids: list<int>
     * }
     */
    public function toArray(): array
    {
        return [
            'node' => $this->node->toArray(),
            'ancestors' => array_map(
                static fn (ScopeNode $node): array => $node->toArray(),
                $this->ancestors,
            ),
            'descendant_subtree' => array_map(
                static fn (ScopeNode $node): array => $node->toArray(),
                $this->descendantSubtree,
            ),
            'descendant_ids' => $this->descendantIds,
        ];
    }

    /**
     * @return array{
     *     node: array{
     *         id: int,
     *         tenant_id: int,
     *         parent_id: int|null,
     *         node_type: string,
     *         name: string,
     *         depth: int,
     *         is_active: bool
     *     },
     *     ancestors: list<array{
     *         id: int,
     *         tenant_id: int,
     *         parent_id: int|null,
     *         node_type: string,
     *         name: string,
     *         depth: int,
     *         is_active: bool
     *     }>,
     *     descendant_subtree: list<array{
     *         id: int,
     *         tenant_id: int,
     *         parent_id: int|null,
     *         node_type: string,
     *         name: string,
     *         depth: int,
     *         is_active: bool
     *     }>,
     *     descendant_ids: list<int>
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
