<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Services;

use App\Domains\Tenancy\Data\ProvisionedOrgNode;
use App\Domains\Tenancy\Data\ProvisionedTenant;
use App\Domains\Tenancy\Data\ProvisioningResult;
use App\Domains\Tenancy\Data\OrgNodeType;
use App\Domains\Tenancy\Events\TenantCreated;
use Illuminate\Support\Facades\DB;

class PlatformTenantProvisioningService
{
    /**
     * @param array{
     *     name: string,
     *     plan_tier?: string,
     *     hierarchy_depth_limit?: int,
     *     root_org_name?: string
     * } $payload
     *
     */
    public function provision(array $payload, int $actorUserId): ProvisioningResult
    {
        $name = trim($payload['name']);
        if ($name === '') {
            throw new \InvalidArgumentException('Tenant name is required.');
        }

        $planTier = isset($payload['plan_tier']) ? trim($payload['plan_tier']) : 'enterprise_pilot';
        if ($planTier === '') {
            $planTier = 'enterprise_pilot';
        }

        $hierarchyDepthLimit = $payload['hierarchy_depth_limit'] ?? 4;
        if ($hierarchyDepthLimit < 1 || $hierarchyDepthLimit > 8) {
            throw new \InvalidArgumentException('Hierarchy depth limit must be between 1 and 8.');
        }

        $rootOrgName = isset($payload['root_org_name']) ? trim($payload['root_org_name']) : $name;
        if ($rootOrgName === '') {
            $rootOrgName = $name;
        }

        $now = now();

        $result = DB::transaction(function () use (
            $actorUserId,
            $hierarchyDepthLimit,
            $name,
            $now,
            $planTier,
            $rootOrgName,
        ): ProvisioningResult {
            $tenantId = (int) DB::table('tenants')->insertGetId([
                'name' => $name,
                'status' => 'active',
                'plan_tier' => $planTier,
                'hierarchy_depth_limit' => $hierarchyDepthLimit,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $rootOrgNodeId = (int) DB::table('org_nodes')->insertGetId([
                'tenant_id' => $tenantId,
                'parent_id' => null,
                'node_type' => OrgNodeType::Company->value,
                'name' => $rootOrgName,
                'depth' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::insert(
                'INSERT INTO tenant_audit_logs
                    (tenant_id, actor_user_id, action, auditable_type, auditable_id, metadata, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $tenantId,
                    $actorUserId,
                    'tenant_created',
                    'tenant',
                    $tenantId,
                    json_encode([
                        'name' => $name,
                        'status' => 'active',
                        'plan_tier' => $planTier,
                        'hierarchy_depth_limit' => $hierarchyDepthLimit,
                        'root_org_node_id' => $rootOrgNodeId,
                    ], JSON_THROW_ON_ERROR),
                    $now,
                    $now,
                ],
            );

            DB::insert(
                'INSERT INTO tenant_audit_logs
                    (tenant_id, actor_user_id, action, auditable_type, auditable_id, metadata, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $tenantId,
                    $actorUserId,
                    'org_node_created',
                    'org_node',
                    $rootOrgNodeId,
                    json_encode([
                        'parent_id' => null,
                        'node_type' => OrgNodeType::Company->value,
                        'name' => $rootOrgName,
                        'depth' => 0,
                    ], JSON_THROW_ON_ERROR),
                    $now,
                    $now,
                ],
            );

            return new ProvisioningResult(
                tenant: new ProvisionedTenant(
                    id: $tenantId,
                    name: $name,
                    status: 'active',
                    planTier: $planTier,
                    hierarchyDepthLimit: $hierarchyDepthLimit,
                ),
                rootOrgNode: new ProvisionedOrgNode(
                    id: $rootOrgNodeId,
                    tenantId: $tenantId,
                    parentId: null,
                    nodeType: OrgNodeType::Company,
                    name: $rootOrgName,
                    depth: 0,
                    isActive: true,
                ),
            );
        });

        event(new TenantCreated(
            tenantId: $result->tenant->id,
            actorUserId: $actorUserId,
            rootOrgNodeId: $result->rootOrgNode->id,
        ));

        return $result;
    }
}
