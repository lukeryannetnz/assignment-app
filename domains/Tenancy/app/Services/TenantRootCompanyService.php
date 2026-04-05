<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Services;

use App\Domains\Tenancy\Data\OrgNodeType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * @phpstan-type RootCompanyNode array{
 *     id: int,
 *     tenant_id: int,
 *     parent_id: null,
 *     node_type: string,
 *     name: string,
 *     depth: int,
 *     is_active: bool
 * }
 */
class TenantRootCompanyService
{
    public function __construct(private readonly TenantAuditLogService $auditLogService)
    {
    }

    /**
     * @return RootCompanyNode
     */
    public function ensureRootCompanyNode(int $tenantId, string $name, ?int $actorUserId): array
    {
        $existingRoot = $this->findRootCompanyNode($tenantId);
        if ($existingRoot !== null) {
            return $existingRoot;
        }

        $trimmedName = trim($name);
        if ($trimmedName === '') {
            throw ValidationException::withMessages([
                'root_company_name' => 'Root company name is required.',
            ]);
        }

        $now = now();

        $rootCompanyId = (int) DB::table('org_nodes')->insertGetId([
            'tenant_id' => $tenantId,
            'parent_id' => null,
            'node_type' => OrgNodeType::Company->value,
            'name' => $trimmedName,
            'depth' => 0,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->auditLogService->recordOrgNodeCreated(
            tenantId: $tenantId,
            actorUserId: $actorUserId,
            orgNodeId: $rootCompanyId,
            metadata: [
                'parent_id' => null,
                'node_type' => OrgNodeType::Company->value,
                'name' => $trimmedName,
                'depth' => 0,
            ],
        );

        return $this->requireRootCompanyNode($tenantId);
    }

    /**
     * @return RootCompanyNode
     */
    public function requireRootCompanyNode(int $tenantId): array
    {
        $rows = $this->rootCompanyRows($tenantId);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'root_company_name' => 'Tenant must have exactly one company root node.',
            ]);
        }

        if (count($rows) > 1) {
            throw ValidationException::withMessages([
                'root_company_name' => 'Tenant has multiple company root nodes and must be repaired before continuing.',
            ]);
        }

        return $rows[0];
    }

    /**
     * @return RootCompanyNode
     */
    public function renameRootCompanyNode(int $tenantId, string $name, ?int $actorUserId): array
    {
        $rootCompany = $this->requireRootCompanyNode($tenantId);
        $trimmedName = trim($name);

        if ($trimmedName === '') {
            throw ValidationException::withMessages([
                'root_company_name' => 'Root company name cannot be empty.',
            ]);
        }

        if ($trimmedName === $rootCompany['name']) {
            return $rootCompany;
        }

        $now = now();

        DB::update(
            'UPDATE org_nodes SET name = ?, updated_at = ? WHERE id = ? AND tenant_id = ?',
            [$trimmedName, $now, $rootCompany['id'], $tenantId],
        );

        $this->auditLogService->recordOrgNodeUpdated(
            tenantId: $tenantId,
            actorUserId: $actorUserId,
            orgNodeId: $rootCompany['id'],
            metadata: [
                'old_name' => $rootCompany['name'],
                'new_name' => $trimmedName,
            ],
        );

        return $this->requireRootCompanyNode($tenantId);
    }

    /**
     * @return RootCompanyNode|null
     */
    public function findRootCompanyNode(int $tenantId): ?array
    {
        $rows = $this->rootCompanyRows($tenantId);

        if ($rows === []) {
            return null;
        }

        if (count($rows) > 1) {
            throw new RuntimeException('Tenant has multiple company root nodes.');
        }

        return $rows[0];
    }

    /**
     * @return list<RootCompanyNode>
     */
    private function rootCompanyRows(int $tenantId): array
    {
        /** @var list<object{id: int, tenant_id: int, parent_id: null, node_type: string, name: string, depth: int, is_active: int|bool}> $rows */
        $rows = DB::select(
            'SELECT id, tenant_id, parent_id, node_type, name, depth, is_active
             FROM org_nodes
             WHERE tenant_id = ?
               AND parent_id IS NULL
               AND node_type = ?
             ORDER BY id ASC',
            [$tenantId, OrgNodeType::Company->value],
        );

        return array_map(static fn (object $row): array => [
            'id' => (int) $row->id,
            'tenant_id' => (int) $row->tenant_id,
            'parent_id' => null,
            'node_type' => (string) $row->node_type,
            'name' => (string) $row->name,
            'depth' => (int) $row->depth,
            'is_active' => (bool) $row->is_active,
        ], $rows);
    }
}
