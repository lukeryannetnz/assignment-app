<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Services;

use App\Domains\Tenancy\Data\ProvisionedOrgNode;
use App\Domains\Tenancy\Data\ProvisionedTenant;
use App\Domains\Tenancy\Data\ProvisioningResult;
use App\Domains\Tenancy\Data\OrgNodeType;
use App\Domains\Tenancy\Data\PlanTier;
use Illuminate\Support\Facades\DB;

class PlatformTenantProvisioningService
{
    public function __construct(
        private readonly TenantRootCompanyService $tenantRootCompanyService,
        private readonly TenantAuditLogService $auditLogService,
    ) {
    }

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

        $planTier = PlanTier::EnterprisePilot;
        if (array_key_exists('plan_tier', $payload)) {
            $planTierValue = is_string($payload['plan_tier']) ? trim($payload['plan_tier']) : '';
            if ($planTierValue !== '') {
                $planTier = PlanTier::from($planTierValue);
            }
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
                'plan_tier' => $planTier->value,
                'hierarchy_depth_limit' => $hierarchyDepthLimit,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $rootOrgNode = $this->tenantRootCompanyService->ensureRootCompanyNode(
                tenantId: $tenantId,
                name: $rootOrgName,
                actorUserId: $actorUserId,
            );
            $rootOrgNodeId = $rootOrgNode['id'];

            $this->auditLogService->recordTenantCreated(
                tenantId: $tenantId,
                actorUserId: $actorUserId,
                metadata: [
                    'name' => $name,
                    'status' => 'active',
                    'plan_tier' => $planTier->value,
                    'hierarchy_depth_limit' => $hierarchyDepthLimit,
                    'root_org_node_id' => $rootOrgNodeId,
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

        return $result;
    }
}
