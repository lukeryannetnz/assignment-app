<?php

declare(strict_types=1);

namespace App\Domains\Skills\Services;

use App\Domains\Skills\Events\RoleMappingCreated;
use App\Domains\Skills\Events\RoleMappingImported;
use App\Domains\Skills\Events\RoleMappingPublished;
use App\Domains\Skills\Events\RoleMappingUpdated;
use Illuminate\Support\Facades\DB;

class RoleMappingTelemetryService
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function recordCreated(int $tenantId, int $roleId, ?int $actorUserId, array $metadata): void
    {
        $this->insertAuditLog($tenantId, $actorUserId, 'role_mapping_created', 'role', $roleId, $metadata);

        DB::afterCommit(function () use ($tenantId, $roleId, $actorUserId, $metadata): void {
            event(new RoleMappingCreated($tenantId, $roleId, $actorUserId, $metadata));
        });
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function recordUpdated(int $tenantId, int $roleId, ?int $actorUserId, array $metadata): void
    {
        $this->insertAuditLog($tenantId, $actorUserId, 'role_mapping_updated', 'role', $roleId, $metadata);

        DB::afterCommit(function () use ($tenantId, $roleId, $actorUserId, $metadata): void {
            event(new RoleMappingUpdated($tenantId, $roleId, $actorUserId, $metadata));
        });
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function recordPublished(int $tenantId, int $roleId, ?int $actorUserId, array $metadata): void
    {
        $this->insertAuditLog($tenantId, $actorUserId, 'role_mapping_published', 'role', $roleId, $metadata);

        DB::afterCommit(function () use ($tenantId, $roleId, $actorUserId, $metadata): void {
            event(new RoleMappingPublished($tenantId, $roleId, $actorUserId, $metadata));
        });
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function recordImported(int $tenantId, ?int $actorUserId, array $metadata): void
    {
        $this->insertAuditLog($tenantId, $actorUserId, 'role_mapping_imported', 'tenant', $tenantId, $metadata);

        DB::afterCommit(function () use ($tenantId, $actorUserId, $metadata): void {
            event(new RoleMappingImported($tenantId, $actorUserId, $metadata));
        });
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
}
