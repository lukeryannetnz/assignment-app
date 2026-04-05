<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Data;

use JsonSerializable;

class ResolvedOrganizationScope implements JsonSerializable
{
    public function __construct(
        public readonly OrgNodeType $scopeType,
        public readonly ScopeNode $requestedNode,
        public readonly OrganizationScope $scope,
    ) {
    }

    /**
     * @return array{
     *     scope_type: string,
     *     requested_node: array{
     *         id: int,
     *         tenant_id: int,
     *         parent_id: int|null,
     *         node_type: string,
     *         name: string,
     *         depth: int,
     *         is_active: bool
     *     },
     *     boundary_node: array{
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
            'scope_type' => $this->scopeType->value,
            'requested_node' => $this->requestedNode->toArray(),
            'boundary_node' => $this->scope->node->toArray(),
            'ancestors' => array_map(
                static fn (ScopeNode $node): array => $node->toArray(),
                $this->scope->ancestors,
            ),
            'descendant_subtree' => array_map(
                static fn (ScopeNode $node): array => $node->toArray(),
                $this->scope->descendantSubtree,
            ),
            'descendant_ids' => $this->scope->descendantIds,
        ];
    }

    /**
     * @return array{
     *     scope_type: string,
     *     requested_node: array{
     *         id: int,
     *         tenant_id: int,
     *         parent_id: int|null,
     *         node_type: string,
     *         name: string,
     *         depth: int,
     *         is_active: bool
     *     },
     *     boundary_node: array{
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
