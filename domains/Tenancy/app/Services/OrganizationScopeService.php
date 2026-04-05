<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Services;

use App\Domains\Tenancy\Data\OrgNodeType;
use App\Domains\Tenancy\Data\OrganizationScope;
use App\Domains\Tenancy\Data\ResolvedOrganizationScope;
use App\Domains\Tenancy\Data\ScopeNode;
use App\Domains\Tenancy\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrganizationScopeService
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function resolveNodeScope(int $nodeId): OrganizationScope
    {
        $tenantId = $this->tenantContext->requireTenantId();
        $this->findNode($tenantId, $nodeId);

        return $this->resolveNodeScopeForTenant($tenantId, $nodeId);
    }

    public function resolveScopedBoundary(int $nodeId, OrgNodeType $scopeType): ResolvedOrganizationScope
    {
        $tenantId = $this->tenantContext->requireTenantId();
        $requestedNode = $this->findNode($tenantId, $nodeId);
        $boundaryNode = $this->resolveBoundaryNodeForTenant($tenantId, $nodeId, $scopeType);

        return new ResolvedOrganizationScope(
            scopeType: $scopeType,
            requestedNode: $requestedNode,
            scope: $this->resolveNodeScopeForTenant($tenantId, $boundaryNode->id),
        );
    }

    /**
     * @return list<ScopeNode>
     */
    public function fetchAncestors(int $nodeId): array
    {
        $tenantId = $this->tenantContext->requireTenantId();
        $this->findNode($tenantId, $nodeId);

        return $this->fetchAncestorsForTenant($tenantId, $nodeId);
    }

    /**
     * @return list<ScopeNode>
     */
    public function fetchDescendantSubtree(int $nodeId): array
    {
        $tenantId = $this->tenantContext->requireTenantId();
        $this->findNode($tenantId, $nodeId);

        return $this->fetchDescendantsForTenant($tenantId, $nodeId);
    }

    /**
     * @return list<int>
     */
    public function fetchDescendantIds(int $nodeId): array
    {
        return array_values(array_map(
            static fn (ScopeNode $row): int => $row->id,
            $this->fetchDescendantSubtree($nodeId),
        ));
    }

    public function resolveBoundaryNode(int $nodeId, OrgNodeType $scopeType): ScopeNode
    {
        $tenantId = $this->tenantContext->requireTenantId();
        $this->findNode($tenantId, $nodeId);

        return $this->resolveBoundaryNodeForTenant($tenantId, $nodeId, $scopeType);
    }

    private function resolveNodeScopeForTenant(int $tenantId, int $nodeId): OrganizationScope
    {
        $node = $this->findNode($tenantId, $nodeId);
        $ancestors = $this->fetchAncestorsForTenant($tenantId, $nodeId);
        $descendantSubtree = $this->fetchDescendantsForTenant($tenantId, $nodeId);

        return new OrganizationScope(
            node: $node,
            ancestors: $ancestors,
            descendantSubtree: $descendantSubtree,
            descendantIds: array_map(
                static fn (ScopeNode $row): int => $row->id,
                $descendantSubtree,
            ),
        );
    }

    /**
     * @return list<ScopeNode>
     */
    private function fetchAncestorsForTenant(int $tenantId, int $nodeId): array
    {
        /** @var list<object{ id: int, tenant_id: int, parent_id: int|null, node_type: string, name: string, depth: int, is_active: int, distance_from_node: int }> $rows */
        $rows = DB::select(
            'WITH RECURSIVE ancestors (
                id,
                tenant_id,
                parent_id,
                node_type,
                name,
                depth,
                is_active,
                distance_from_node
            ) AS (
                SELECT id, tenant_id, parent_id, node_type, name, depth, is_active, 0
                FROM org_nodes
                WHERE id = ? AND tenant_id = ?
                UNION ALL
                SELECT parent.id,
                       parent.tenant_id,
                       parent.parent_id,
                       parent.node_type,
                       parent.name,
                       parent.depth,
                       parent.is_active,
                       ancestors.distance_from_node + 1
                FROM org_nodes AS parent
                INNER JOIN ancestors ON parent.id = ancestors.parent_id
                WHERE parent.tenant_id = ?
            )
            SELECT id, tenant_id, parent_id, node_type, name, depth, is_active, distance_from_node
            FROM ancestors
            WHERE distance_from_node > 0
            ORDER BY distance_from_node DESC, id ASC',
            [$nodeId, $tenantId, $tenantId],
        );

        return array_values(array_map(
            static fn (object $row): ScopeNode => ScopeNode::fromRow($row),
            $rows,
        ));
    }

    /**
     * @return list<ScopeNode>
     */
    private function fetchDescendantsForTenant(int $tenantId, int $nodeId): array
    {
        /** @var list<object{ id: int, tenant_id: int, parent_id: int|null, node_type: string, name: string, depth: int, is_active: int, distance_from_node: int }> $rows */
        $rows = DB::select(
            'WITH RECURSIVE descendants (
                id,
                tenant_id,
                parent_id,
                node_type,
                name,
                depth,
                is_active,
                distance_from_node
            ) AS (
                SELECT id, tenant_id, parent_id, node_type, name, depth, is_active, 0
                FROM org_nodes
                WHERE id = ? AND tenant_id = ?
                UNION ALL
                SELECT child.id,
                       child.tenant_id,
                       child.parent_id,
                       child.node_type,
                       child.name,
                       child.depth,
                       child.is_active,
                       descendants.distance_from_node + 1
                FROM org_nodes AS child
                INNER JOIN descendants ON child.parent_id = descendants.id
                WHERE child.tenant_id = ?
            )
            SELECT id, tenant_id, parent_id, node_type, name, depth, is_active, distance_from_node
            FROM descendants
            WHERE distance_from_node > 0
            ORDER BY distance_from_node ASC, depth ASC, id ASC',
            [$nodeId, $tenantId, $tenantId],
        );

        return array_values(array_map(
            static fn (object $row): ScopeNode => ScopeNode::fromRow($row),
            $rows,
        ));
    }

    private function resolveBoundaryNodeForTenant(
        int $tenantId,
        int $nodeId,
        OrgNodeType $scopeType,
    ): ScopeNode {
        $boundaryNode = $this->findSelfOrAncestorByNodeType($tenantId, $nodeId, $scopeType->value);

        if ($boundaryNode === null) {
            throw ValidationException::withMessages([
                'scope' => sprintf(
                    '%s scope is unavailable for this organization node.',
                    ucfirst(str_replace('_', ' ', $scopeType->value)),
                ),
            ]);
        }

        return $boundaryNode;
    }

    private function findSelfOrAncestorByNodeType(int $tenantId, int $nodeId, string $nodeType): ?ScopeNode
    {
        /** @var object{ id: int, tenant_id: int, parent_id: int|null, node_type: string, name: string, depth: int, is_active: int }|null $row */
        $row = DB::selectOne(
            'WITH RECURSIVE lineage (
                id,
                tenant_id,
                parent_id,
                node_type,
                name,
                depth,
                is_active,
                distance_from_node
            ) AS (
                SELECT id, tenant_id, parent_id, node_type, name, depth, is_active, 0
                FROM org_nodes
                WHERE id = ? AND tenant_id = ?
                UNION ALL
                SELECT parent.id,
                       parent.tenant_id,
                       parent.parent_id,
                       parent.node_type,
                       parent.name,
                       parent.depth,
                       parent.is_active,
                       lineage.distance_from_node + 1
                FROM org_nodes AS parent
                INNER JOIN lineage ON parent.id = lineage.parent_id
                WHERE parent.tenant_id = ?
            )
            SELECT id, tenant_id, parent_id, node_type, name, depth, is_active
            FROM lineage
            WHERE node_type = ?
            ORDER BY distance_from_node ASC, id ASC
            LIMIT 1',
            [$nodeId, $tenantId, $tenantId, $nodeType],
        );

        if ($row === null) {
            return null;
        }

        return ScopeNode::fromRow($row);
    }

    private function findNode(int $tenantId, int $nodeId): ScopeNode
    {
        /** @var object{ id: int, tenant_id: int, parent_id: int|null, node_type: string, name: string, depth: int, is_active: int }|null $row */
        $row = DB::selectOne(
            'SELECT id, tenant_id, parent_id, node_type, name, depth, is_active
             FROM org_nodes
             WHERE id = ? AND tenant_id = ?
             LIMIT 1',
            [$nodeId, $tenantId],
        );

        if ($row === null) {
            throw ValidationException::withMessages([
                'node' => 'Organization node was not found.',
            ]);
        }

        return ScopeNode::fromRow($row);
    }
}
