<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Services;

use App\Domains\Tenancy\Data\OrgNodeType;
use App\Domains\Tenancy\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * @phpstan-type TenantRow array{
 *     id: int,
 *     hierarchy_depth_limit: int
 * }
 * @phpstan-type NodeRow array{
 *     id: int,
 *     tenant_id: int,
 *     parent_id: int|null,
 *     node_type: string,
 *     name: string,
 *     depth: int,
 *     is_active: bool
 * }
 */
class OrganizationHierarchyService
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    /**
     * @return list<NodeRow>
     */
    public function listNodes(): array
    {
        $tenantId = $this->tenantContext->requireTenantId();

        /** @var list<object{ id: int, tenant_id: int, parent_id: int|null, node_type: string, name: string, depth: int, is_active: int }> $rows */
        $rows = DB::select(
            'SELECT id, tenant_id, parent_id, node_type, name, depth, is_active
             FROM org_nodes
             WHERE tenant_id = ?
             ORDER BY depth ASC, id ASC',
            [$tenantId],
        );

        return array_map(static fn (object $row): array => [
            'id' => (int) $row->id,
            'tenant_id' => (int) $row->tenant_id,
            'parent_id' => $row->parent_id !== null ? (int) $row->parent_id : null,
            'node_type' => (string) $row->node_type,
            'name' => (string) $row->name,
            'depth' => (int) $row->depth,
            'is_active' => (bool) $row->is_active,
        ], $rows);
    }

    /**
     * @param  array{name: mixed, node_type: mixed, parent_id?: mixed}  $payload
     * @return NodeRow
     */
    public function createNode(array $payload, ?int $actorUserId): array
    {
        $tenantId = $this->tenantContext->requireTenantId();
        $tenant = $this->findTenant($tenantId);

        $nameValue = $payload['name'] ?? null;
        $name = is_string($nameValue) ? trim($nameValue) : '';
        if ($name === '') {
            $this->failHierarchyValidation(
                tenantId: $tenantId,
                actorUserId: $actorUserId,
                operation: 'create_node',
                messages: ['name' => 'Node name is required.'],
            );
        }

        $nodeTypeValue = $payload['node_type'] ?? null;
        $nodeType = is_string($nodeTypeValue) ? OrgNodeType::tryFrom($nodeTypeValue) : null;
        if ($nodeType === null) {
            $this->failHierarchyValidation(
                tenantId: $tenantId,
                actorUserId: $actorUserId,
                operation: 'create_node',
                messages: ['node_type' => 'Invalid node type.'],
            );
        }

        $parentId = null;
        if (array_key_exists('parent_id', $payload) && $payload['parent_id'] !== null) {
            $parentIdValue = $payload['parent_id'];
            if (!is_numeric($parentIdValue)) {
                $this->failHierarchyValidation(
                    tenantId: $tenantId,
                    actorUserId: $actorUserId,
                    operation: 'create_node',
                    messages: ['parent_id' => 'Parent ID must be numeric.'],
                );
            }

            $parentId = (int) $parentIdValue;
        }
        $depth = 0;
        if ($parentId !== null) {
            $parent = $this->findNode($tenantId, $parentId);
            $depth = $parent['depth'] + 1;
        }

        if ($depth >= $tenant['hierarchy_depth_limit']) {
            $this->failHierarchyValidation(
                tenantId: $tenantId,
                actorUserId: $actorUserId,
                operation: 'create_node',
                messages: [
                    'parent_id' => sprintf(
                        'Hierarchy depth cannot exceed tenant limit (%d).',
                        $tenant['hierarchy_depth_limit'],
                    ),
                ],
            );
        }

        $nodeId = (int) DB::table('org_nodes')->insertGetId([
            'tenant_id' => $tenantId,
            'parent_id' => $parentId,
            'node_type' => $nodeType->value,
            'name' => $name,
            'depth' => $depth,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createAuditLog(
            tenantId: $tenantId,
            actorUserId: $actorUserId,
            action: 'org_node_created',
            auditableType: 'org_node',
            auditableId: $nodeId,
            metadata: ['parent_id' => $parentId, 'depth' => $depth],
        );

        return $this->findNode($tenantId, $nodeId);
    }

    /**
     * @param  array{name?: mixed}  $payload
     * @return NodeRow
     */
    public function updateNode(int $nodeId, array $payload, ?int $actorUserId): array
    {
        $tenantId = $this->tenantContext->requireTenantId();
        $node = $this->findNode($tenantId, $nodeId);

        if (!array_key_exists('name', $payload)) {
            $this->failHierarchyValidation(
                tenantId: $tenantId,
                actorUserId: $actorUserId,
                operation: 'update_node',
                messages: ['name' => 'Name must be provided.'],
            );
        }

        $nameValue = $payload['name'];
        $name = is_string($nameValue) ? trim($nameValue) : '';
        if ($name === '') {
            $this->failHierarchyValidation(
                tenantId: $tenantId,
                actorUserId: $actorUserId,
                operation: 'update_node',
                messages: ['name' => 'Node name cannot be empty.'],
            );
        }

        DB::update(
            'UPDATE org_nodes SET name = ?, updated_at = ? WHERE id = ? AND tenant_id = ?',
            [$name, now(), $nodeId, $tenantId],
        );

        $this->createAuditLog(
            tenantId: $tenantId,
            actorUserId: $actorUserId,
            action: 'org_node_updated',
            auditableType: 'org_node',
            auditableId: $nodeId,
            metadata: ['old_name' => $node['name'], 'new_name' => $name],
        );

        return $this->findNode($tenantId, $nodeId);
    }

    /**
     * @return NodeRow
     */
    public function moveNode(int $nodeId, int $parentId, ?int $actorUserId): array
    {
        $tenantId = $this->tenantContext->requireTenantId();
        $tenant = $this->findTenant($tenantId);
        $node = $this->findNode($tenantId, $nodeId);
        $newParent = $this->findNode($tenantId, $parentId);

        if ($nodeId === $parentId) {
            $this->failHierarchyValidation(
                tenantId: $tenantId,
                actorUserId: $actorUserId,
                operation: 'move_node',
                messages: ['parent_id' => 'Node cannot be its own parent.'],
            );
        }

        if ($this->isDescendant($tenantId, $newParent['id'], $nodeId)) {
            $this->failHierarchyValidation(
                tenantId: $tenantId,
                actorUserId: $actorUserId,
                operation: 'move_node',
                messages: ['parent_id' => 'Move rejected because it creates a cycle.'],
            );
        }

        $subtree = $this->subtreeDepthOffsets($tenantId, $nodeId);
        $maxOffset = 0;
        foreach ($subtree as $item) {
            $maxOffset = max($maxOffset, $item['depth_offset']);
        }

        $targetDepth = $newParent['depth'] + 1;
        if ($targetDepth + $maxOffset >= $tenant['hierarchy_depth_limit']) {
            $this->failHierarchyValidation(
                tenantId: $tenantId,
                actorUserId: $actorUserId,
                operation: 'move_node',
                messages: [
                    'parent_id' => sprintf(
                        'Move exceeds hierarchy depth limit (%d).',
                        $tenant['hierarchy_depth_limit'],
                    ),
                ],
            );
        }

        DB::transaction(function () use ($subtree, $targetDepth, $tenantId, $nodeId, $parentId): void {
            DB::update(
                'UPDATE org_nodes SET parent_id = ?, depth = ?, updated_at = ? WHERE id = ? AND tenant_id = ?',
                [$parentId, $targetDepth, now(), $nodeId, $tenantId],
            );

            foreach ($subtree as $item) {
                if ($item['id'] === $nodeId) {
                    continue;
                }

                DB::update(
                    'UPDATE org_nodes SET depth = ?, updated_at = ? WHERE id = ? AND tenant_id = ?',
                    [$targetDepth + $item['depth_offset'], now(), $item['id'], $tenantId],
                );
            }
        });

        $this->createAuditLog(
            tenantId: $tenantId,
            actorUserId: $actorUserId,
            action: 'org_node_moved',
            auditableType: 'org_node',
            auditableId: $nodeId,
            metadata: ['old_parent_id' => $node['parent_id'], 'new_parent_id' => $parentId],
        );

        return $this->findNode($tenantId, $nodeId);
    }

    /**
     * @return NodeRow
     */
    public function deactivateNode(int $nodeId, ?int $actorUserId): array
    {
        $tenantId = $this->tenantContext->requireTenantId();
        $node = $this->findNode($tenantId, $nodeId);

        $subtreeNodeIds = array_column($this->subtreeDepthOffsets($tenantId, $nodeId), 'id');

        $activeDescendants = (int) DB::table('org_nodes')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $subtreeNodeIds)
            ->where('id', '!=', $nodeId)
            ->where('is_active', true)
            ->count();

        if ($activeDescendants > 0) {
            $this->failHierarchyValidation(
                tenantId: $tenantId,
                actorUserId: $actorUserId,
                operation: 'deactivate_node',
                messages: ['node' => 'Deactivate all active descendant nodes first to avoid orphan active nodes.'],
            );
        }

        DB::update(
            'UPDATE org_nodes SET is_active = ?, updated_at = ? WHERE id = ? AND tenant_id = ?',
            [false, now(), $nodeId, $tenantId],
        );

        $this->createAuditLog(
            tenantId: $tenantId,
            actorUserId: $actorUserId,
            action: 'org_node_deactivated',
            auditableType: 'org_node',
            auditableId: $nodeId,
            metadata: ['previous_state' => $node['is_active']],
        );

        return $this->findNode($tenantId, $nodeId);
    }

    /**
     * @return NodeRow
     */
    public function reactivateNode(int $nodeId, ?int $actorUserId): array
    {
        $tenantId = $this->tenantContext->requireTenantId();
        $node = $this->findNode($tenantId, $nodeId);

        if ($node['parent_id'] !== null) {
            $parent = $this->findNode($tenantId, (int) $node['parent_id']);
            if (!$parent['is_active']) {
                $this->failHierarchyValidation(
                    tenantId: $tenantId,
                    actorUserId: $actorUserId,
                    operation: 'reactivate_node',
                    messages: ['node' => 'Parent node must be active before reactivation.'],
                );
            }
        }

        DB::update(
            'UPDATE org_nodes SET is_active = ?, updated_at = ? WHERE id = ? AND tenant_id = ?',
            [true, now(), $nodeId, $tenantId],
        );

        $this->createAuditLog(
            tenantId: $tenantId,
            actorUserId: $actorUserId,
            action: 'org_node_updated',
            auditableType: 'org_node',
            auditableId: $nodeId,
            metadata: ['reactivated' => true],
        );

        return $this->findNode($tenantId, $nodeId);
    }

    /**
     * @return TenantRow
     */
    private function findTenant(int $tenantId): array
    {
        /** @var object{ id: int, hierarchy_depth_limit: int }|null $row */
        $row = DB::selectOne(
            'SELECT id, hierarchy_depth_limit FROM tenants WHERE id = ? LIMIT 1',
            [$tenantId],
        );

        if ($row === null) {
            throw new RuntimeException('Tenant was not found for current context.');
        }

        return [
            'id' => (int) $row->id,
            'hierarchy_depth_limit' => (int) $row->hierarchy_depth_limit,
        ];
    }

    /**
     * @return NodeRow
     */
    private function findNode(int $tenantId, int $nodeId): array
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
            throw ValidationException::withMessages(['node' => 'Organization node was not found.']);
        }

        return [
            'id' => (int) $row->id,
            'tenant_id' => (int) $row->tenant_id,
            'parent_id' => $row->parent_id !== null ? (int) $row->parent_id : null,
            'node_type' => (string) $row->node_type,
            'name' => (string) $row->name,
            'depth' => (int) $row->depth,
            'is_active' => (bool) $row->is_active,
        ];
    }

    /**
     * @return list<array{id: int, depth_offset: int}>
     */
    private function subtreeDepthOffsets(int $tenantId, int $nodeId): array
    {
        /** @var list<object{ id: int, depth_offset: int }> $rows */
        $rows = DB::select(
            'WITH RECURSIVE subtree(id, depth_offset) AS (
                SELECT id, 0
                FROM org_nodes
                WHERE id = ? AND tenant_id = ?
                UNION ALL
                SELECT child.id, subtree.depth_offset + 1
                FROM org_nodes AS child
                INNER JOIN subtree ON child.parent_id = subtree.id
                WHERE child.tenant_id = ?
            )
            SELECT id, depth_offset FROM subtree',
            [$nodeId, $tenantId, $tenantId],
        );

        return array_map(static fn (object $row): array => [
            'id' => (int) $row->id,
            'depth_offset' => (int) $row->depth_offset,
        ], $rows);
    }

    private function isDescendant(int $tenantId, int $candidateParentId, int $nodeId): bool
    {
        $currentParentId = $candidateParentId;

        while (true) {
            if ($currentParentId === $nodeId) {
                return true;
            }

            /** @var object{ parent_id: int|null }|null $row */
            $row = DB::selectOne(
                'SELECT parent_id FROM org_nodes WHERE id = ? AND tenant_id = ? LIMIT 1',
                [$currentParentId, $tenantId],
            );

            if ($row === null || $row->parent_id === null) {
                return false;
            }

            $currentParentId = (int) $row->parent_id;
        }
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function createAuditLog(
        int $tenantId,
        ?int $actorUserId,
        string $action,
        string $auditableType,
        int $auditableId,
        array $metadata,
    ): void {
        DB::insert(
            'INSERT INTO tenant_audit_logs
                (tenant_id, actor_user_id, action, auditable_type, auditable_id, metadata, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $tenantId,
                $actorUserId,
                $action,
                $auditableType,
                $auditableId,
                json_encode($metadata, JSON_THROW_ON_ERROR),
                now(),
                now(),
            ],
        );
    }

    /**
     * @param  array<string, string>  $messages
     */
    private function failHierarchyValidation(
        int $tenantId,
        ?int $actorUserId,
        string $operation,
        array $messages,
    ): never {
        $this->createAuditLog(
            tenantId: $tenantId,
            actorUserId: $actorUserId,
            action: 'hierarchy_integrity_error',
            auditableType: 'tenant',
            auditableId: $tenantId,
            metadata: [
                'operation' => $operation,
                'messages' => $messages,
            ],
        );

        throw ValidationException::withMessages($messages);
    }
}
