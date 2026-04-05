<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Services;

use App\Domains\Tenancy\Events\OrgNodeCreated;
use App\Domains\Tenancy\Events\OrgNodeDeactivated;
use App\Domains\Tenancy\Events\OrgNodeMoved;
use App\Domains\Tenancy\Events\OrgNodeUpdated;
use App\Domains\Tenancy\Events\TenantCreated;
use Illuminate\Support\Facades\DB;

class TenantAuditLogService
{
    private const RETENTION_MONTHS = 12;

    /**
     * @param array<string, mixed> $metadata
     */
    public function recordTenantCreated(int $tenantId, ?int $actorUserId, array $metadata): void
    {
        $this->insertAuditLog($tenantId, $actorUserId, 'tenant_created', 'tenant', $tenantId, $metadata);

        DB::afterCommit(function () use ($actorUserId, $metadata, $tenantId): void {
            $rootOrgNodeId = $metadata['root_org_node_id'] ?? null;
            if (!is_int($rootOrgNodeId) && !is_string($rootOrgNodeId) && !is_float($rootOrgNodeId)) {
                throw new \InvalidArgumentException('Tenant created audit metadata requires root_org_node_id.');
            }

            event(new TenantCreated(
                rootOrgNodeId: (int) $rootOrgNodeId,
                tenantId: $tenantId,
                actorUserId: $actorUserId,
                metadata: $metadata,
            ));
        });
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function recordTenantUpdated(int $tenantId, ?int $actorUserId, array $metadata): void
    {
        $this->insertAuditLog($tenantId, $actorUserId, 'tenant_updated', 'tenant', $tenantId, $metadata);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function recordOrgNodeCreated(int $tenantId, ?int $actorUserId, int $orgNodeId, array $metadata): void
    {
        $this->insertAuditLog($tenantId, $actorUserId, 'org_node_created', 'org_node', $orgNodeId, $metadata);

        DB::afterCommit(function () use ($actorUserId, $metadata, $orgNodeId, $tenantId): void {
            event(new OrgNodeCreated(
                orgNodeId: $orgNodeId,
                tenantId: $tenantId,
                actorUserId: $actorUserId,
                metadata: $metadata,
            ));
        });
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function recordOrgNodeUpdated(int $tenantId, ?int $actorUserId, int $orgNodeId, array $metadata): void
    {
        $this->insertAuditLog($tenantId, $actorUserId, 'org_node_updated', 'org_node', $orgNodeId, $metadata);

        DB::afterCommit(function () use ($actorUserId, $metadata, $orgNodeId, $tenantId): void {
            event(new OrgNodeUpdated(
                orgNodeId: $orgNodeId,
                tenantId: $tenantId,
                actorUserId: $actorUserId,
                metadata: $metadata,
            ));
        });
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function recordOrgNodeMoved(int $tenantId, ?int $actorUserId, int $orgNodeId, array $metadata): void
    {
        $this->insertAuditLog($tenantId, $actorUserId, 'org_node_moved', 'org_node', $orgNodeId, $metadata);

        DB::afterCommit(function () use ($actorUserId, $metadata, $orgNodeId, $tenantId): void {
            event(new OrgNodeMoved(
                orgNodeId: $orgNodeId,
                tenantId: $tenantId,
                actorUserId: $actorUserId,
                metadata: $metadata,
            ));
        });
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function recordOrgNodeDeactivated(int $tenantId, ?int $actorUserId, int $orgNodeId, array $metadata): void
    {
        $this->insertAuditLog($tenantId, $actorUserId, 'org_node_deactivated', 'org_node', $orgNodeId, $metadata);

        DB::afterCommit(function () use ($actorUserId, $metadata, $orgNodeId, $tenantId): void {
            event(new OrgNodeDeactivated(
                orgNodeId: $orgNodeId,
                tenantId: $tenantId,
                actorUserId: $actorUserId,
                metadata: $metadata,
            ));
        });
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function recordHierarchyIntegrityError(int $tenantId, ?int $actorUserId, array $metadata): void
    {
        $this->insertAuditLog(
            tenantId: $tenantId,
            actorUserId: $actorUserId,
            action: 'hierarchy_integrity_error',
            auditableType: 'tenant',
            auditableId: $tenantId,
            metadata: $metadata,
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function recordAction(
        int $tenantId,
        ?int $actorUserId,
        string $action,
        string $auditableType,
        int $auditableId,
        array $metadata,
    ): void {
        $this->insertAuditLog(
            tenantId: $tenantId,
            actorUserId: $actorUserId,
            action: $action,
            auditableType: $auditableType,
            auditableId: $auditableId,
            metadata: $metadata,
        );
    }

    /**
     * @return list<array{
     *     id: int,
     *     tenant_id: int,
     *     actor_user_id: int|null,
     *     action: string,
     *     auditable_type: string,
     *     auditable_id: int,
     *     metadata: array<string, mixed>,
     *     created_at: string
     * }>
     */
    public function listRecentLogs(int $tenantId, int $limit = 50): array
    {
        /** @var list<object{
         *     id: int,
         *     tenant_id: int,
         *     actor_user_id: int|null,
         *     action: string,
         *     auditable_type: string,
         *     auditable_id: int,
         *     metadata: string|null,
         *     created_at: string
         * }> $rows
         */
        $rows = DB::select(
            'SELECT id, tenant_id, actor_user_id, action, auditable_type, auditable_id, metadata, created_at
             FROM tenant_audit_logs
             WHERE tenant_id = ?
               AND created_at >= ?
             ORDER BY created_at DESC, id DESC
             LIMIT ?',
            [$tenantId, $this->retentionWindowStart(), $limit],
        );

        return array_map(static function (object $row): array {
            /** @var array<string, mixed> $metadata */
            $metadata = $row->metadata !== null
                ? json_decode($row->metadata, true, 512, JSON_THROW_ON_ERROR)
                : [];

            return [
                'id' => (int) $row->id,
                'tenant_id' => (int) $row->tenant_id,
                'actor_user_id' => $row->actor_user_id !== null ? (int) $row->actor_user_id : null,
                'action' => (string) $row->action,
                'auditable_type' => (string) $row->auditable_type,
                'auditable_id' => (int) $row->auditable_id,
                'metadata' => $metadata,
                'created_at' => (string) $row->created_at,
            ];
        }, $rows);
    }

    /**
     * @return array{
     *     minimum_retention_months: int,
     *     retention_window_start: string,
     *     access_scope: string,
     *     checklist: list<string>
     * }
     */
    public function complianceSummary(): array
    {
        return [
            'minimum_retention_months' => self::RETENTION_MONTHS,
            'retention_window_start' => $this->retentionWindowStart(),
            'access_scope' => 'Authenticated tenant admins scoped to their current tenant context.',
            'checklist' => [
                'Verify all tenancy admin routes require auth, admin role, and tenant context before pilot launch.',
                'Review recent audit rows for actor, action, entity, and metadata completeness.',
                'Confirm audit exports or pruning decisions preserve at least 12 months of records.',
                'Confirm analytics consumers subscribe to tenancy lifecycle events without cross-tenant joins.',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function insertAuditLog(
        int $tenantId,
        ?int $actorUserId,
        string $action,
        string $auditableType,
        int $auditableId,
        array $metadata,
    ): void {
        $now = now();

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
                $now,
                $now,
            ],
        );
    }

    private function retentionWindowStart(): string
    {
        return now()->subMonths(self::RETENTION_MONTHS)->toDateTimeString();
    }
}
