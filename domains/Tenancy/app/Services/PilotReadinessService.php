<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Services;

use App\Domains\Tenancy\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class PilotReadinessService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly OrganizationHierarchyTemplateService $templateService,
    ) {
    }

    /**
     * @return array{
     *     tenant: array{
     *         id: int,
     *         name: string,
     *         status: string,
     *         plan_tier: string,
     *         hierarchy_depth_limit: int,
     *         root_company_name: string
     *     },
     *     metrics: array{
     *         onboarding_duration_hours: float|null,
     *         onboarding_started_at: string|null,
     *         assignment_ready_at: string|null,
     *         hierarchy_integrity_error_rate: float,
     *         hierarchy_write_count: int,
     *         hierarchy_integrity_error_count: int,
     *         active_non_root_node_count: int,
     *         active_team_count: int
     *     },
     *     onboarding_checklist: list<array{
     *         title: string,
     *         owner: string,
     *         status: string,
     *         detail: string
     *     }>,
     *     go_no_go_checklist: list<array{
     *         title: string,
     *         owner: string,
     *         status: string,
     *         detail: string
     *     }>,
     *     playbook: list<array{
     *         phase: string,
     *         owner: string,
     *         guidance: string
     *     }>,
     *     templates: list<array{
     *         key: string,
     *         name: string,
     *         description: string,
     *         row_count: int,
     *         download_url: string
     *     }>
     * }
     */
    public function summary(): array
    {
        $tenantId = $this->tenantContext->requireTenantId();

        /** @var object{
         *     id: int,
         *     name: string,
         *     status: string,
         *     plan_tier: string,
         *     hierarchy_depth_limit: int,
         *     root_company_name: string
         * }|null $tenant
         */
        $tenant = DB::selectOne(
            'SELECT t.id, t.name, t.status, t.plan_tier, t.hierarchy_depth_limit, root.name AS root_company_name
             FROM tenants AS t
             INNER JOIN org_nodes AS root
                 ON root.tenant_id = t.id
                AND root.parent_id IS NULL
                AND root.node_type = ?
             WHERE t.id = ?
             LIMIT 1',
            ['company', $tenantId],
        );

        if ($tenant === null) {
            throw new \RuntimeException('Current tenant could not be resolved for pilot readiness.');
        }

        /** @var object{
         *     onboarding_started_at: string|null,
         *     assignment_ready_at: string|null,
         *     hierarchy_write_count: int,
         *     hierarchy_integrity_error_count: int
         * } $auditMetrics
         */
        $auditMetrics = DB::selectOne(
            'SELECT
                MIN(CASE WHEN action = ? THEN created_at END) AS onboarding_started_at,
                MIN(CASE
                    WHEN action = ?
                     AND auditable_type = ?
                     AND EXISTS (
                        SELECT 1
                        FROM org_nodes
                        WHERE id = tenant_audit_logs.auditable_id
                          AND tenant_id = tenant_audit_logs.tenant_id
                          AND node_type = ?
                          AND is_active = 1
                    ) THEN created_at
                END) AS assignment_ready_at,
                SUM(CASE WHEN action IN (?, ?, ?, ?) THEN 1 ELSE 0 END) AS hierarchy_write_count,
                SUM(CASE WHEN action = ? THEN 1 ELSE 0 END) AS hierarchy_integrity_error_count
             FROM tenant_audit_logs
             WHERE tenant_id = ?',
            [
                'tenant_created',
                'org_node_created',
                'org_node',
                'team',
                'org_node_created',
                'org_node_updated',
                'org_node_moved',
                'org_node_deactivated',
                'hierarchy_integrity_error',
                $tenantId,
            ],
        );

        /** @var object{active_non_root_node_count: int, active_team_count: int} $nodeMetrics */
        $nodeMetrics = DB::selectOne(
            'SELECT
                SUM(CASE WHEN parent_id IS NOT NULL AND is_active = 1 THEN 1 ELSE 0 END) AS active_non_root_node_count,
                SUM(CASE WHEN node_type = ? AND is_active = 1 THEN 1 ELSE 0 END) AS active_team_count
             FROM org_nodes
             WHERE tenant_id = ?',
            ['team', $tenantId],
        );

        $hierarchyWriteCount = (int) $auditMetrics->hierarchy_write_count;
        $integrityErrorCount = (int) $auditMetrics->hierarchy_integrity_error_count;
        $attemptedWriteCount = $hierarchyWriteCount + $integrityErrorCount;
        $errorRate = $attemptedWriteCount === 0 ? 0.0 : round(($integrityErrorCount / $attemptedWriteCount) * 100, 2);
        $startedAt = $auditMetrics->onboarding_started_at;
        $assignmentReadyAt = $auditMetrics->assignment_ready_at;

        $onboardingDurationHours = null;
        if (is_string($startedAt) && is_string($assignmentReadyAt)) {
            $onboardingDurationHours = round(
                (strtotime($assignmentReadyAt) - strtotime($startedAt)) / 3600,
                2,
            );
        }

        $metrics = [
            'onboarding_duration_hours' => $onboardingDurationHours,
            'onboarding_started_at' => is_string($startedAt) ? $startedAt : null,
            'assignment_ready_at' => is_string($assignmentReadyAt) ? $assignmentReadyAt : null,
            'hierarchy_integrity_error_rate' => $errorRate,
            'hierarchy_write_count' => $hierarchyWriteCount,
            'hierarchy_integrity_error_count' => $integrityErrorCount,
            'active_non_root_node_count' => (int) $nodeMetrics->active_non_root_node_count,
            'active_team_count' => (int) $nodeMetrics->active_team_count,
        ];

        return [
            'tenant' => [
                'id' => (int) $tenant->id,
                'name' => (string) $tenant->name,
                'status' => (string) $tenant->status,
                'plan_tier' => (string) $tenant->plan_tier,
                'hierarchy_depth_limit' => (int) $tenant->hierarchy_depth_limit,
                'root_company_name' => (string) $tenant->root_company_name,
            ],
            'metrics' => $metrics,
            'onboarding_checklist' => $this->buildOnboardingChecklist($tenant, $metrics),
            'go_no_go_checklist' => $this->buildGoNoGoChecklist($tenant, $metrics),
            'playbook' => $this->buildPlaybook(),
            'templates' => $this->buildTemplates(),
        ];
    }

    /**
     * @param  object{
     *     id: int,
     *     name: string,
     *     status: string,
     *     plan_tier: string,
     *     hierarchy_depth_limit: int,
     *     root_company_name: string
     * }  $tenant
     * @param  array{
     *     onboarding_duration_hours: float|null,
     *     onboarding_started_at: string|null,
     *     assignment_ready_at: string|null,
     *     hierarchy_integrity_error_rate: float,
     *     hierarchy_write_count: int,
     *     hierarchy_integrity_error_count: int,
     *     active_non_root_node_count: int,
     *     active_team_count: int
     * }  $metrics
     * @return list<array{
     *     title: string,
     *     owner: string,
     *     status: string,
     *     detail: string
     * }>
     */
    private function buildOnboardingChecklist(object $tenant, array $metrics): array
    {
        return [
            [
                'title' => 'Provision tenant shell',
                'owner' => 'Engineering',
                'status' => 'complete',
                'detail' => sprintf(
                    'Tenant %s is active with plan %s and depth limit %d.',
                    $tenant->name,
                    $tenant->plan_tier,
                    $tenant->hierarchy_depth_limit,
                ),
            ],
            [
                'title' => 'Confirm root company configuration',
                'owner' => 'Customer Success',
                'status' => trim($tenant->root_company_name) !== '' ? 'complete' : 'pending',
                'detail' => sprintf('Root company node is currently named %s.', $tenant->root_company_name),
            ],
            [
                'title' => 'Load a starter org structure',
                'owner' => 'Customer Success',
                'status' => $metrics['active_non_root_node_count'] > 0 ? 'complete' : 'pending',
                'detail' => $metrics['active_non_root_node_count'] > 0
                    ? sprintf(
                        '%d active non-root nodes already exist in the pilot hierarchy.',
                        $metrics['active_non_root_node_count'],
                    )
                    : 'Use a template CSV or manual node creation to build the initial hierarchy.',
            ],
            [
                'title' => 'Reach assignment-ready hierarchy',
                'owner' => 'Engineering',
                'status' => $metrics['active_team_count'] > 0 ? 'complete' : 'pending',
                'detail' => $metrics['active_team_count'] > 0
                    ? sprintf(
                        'The pilot has %d active team nodes. First assignment-ready timestamp: %s.',
                        $metrics['active_team_count'],
                        (string) $metrics['assignment_ready_at'],
                    )
                    : 'Add at least one active team node so setup duration can be measured end to end.',
            ],
            [
                'title' => 'Review KPI instrumentation',
                'owner' => 'Product',
                'status' => $metrics['hierarchy_write_count'] > 0 ? 'complete' : 'pending',
                'detail' => sprintf(
                    'Hierarchy write ops: %d. Integrity errors: %d. Error rate: %.2f%%.',
                    $metrics['hierarchy_write_count'],
                    $metrics['hierarchy_integrity_error_count'],
                    $metrics['hierarchy_integrity_error_rate'],
                ),
            ],
        ];
    }

    /**
     * @param  object{
     *     id: int,
     *     name: string,
     *     status: string,
     *     plan_tier: string,
     *     hierarchy_depth_limit: int,
     *     root_company_name: string
     * }  $tenant
     * @param  array{
     *     onboarding_duration_hours: float|null,
     *     onboarding_started_at: string|null,
     *     assignment_ready_at: string|null,
     *     hierarchy_integrity_error_rate: float,
     *     hierarchy_write_count: int,
     *     hierarchy_integrity_error_count: int,
     *     active_non_root_node_count: int,
     *     active_team_count: int
     * }  $metrics
     * @return list<array{
     *     title: string,
     *     owner: string,
     *     status: string,
     *     detail: string
     * }>
     */
    private function buildGoNoGoChecklist(object $tenant, array $metrics): array
    {
        $setupDurationStatus = $metrics['onboarding_duration_hours'] !== null
            && $metrics['onboarding_duration_hours'] <= 24 * 14
            ? 'go'
            : 'hold';
        $errorRateStatus = $metrics['hierarchy_integrity_error_rate'] <= 2.0 ? 'go' : 'hold';
        $assignmentStatus = $metrics['active_team_count'] > 0 ? 'go' : 'hold';

        return [
            [
                'title' => 'Internal alpha can be validated without database intervention',
                'owner' => 'Engineering',
                'status' => $tenant->status === 'active' && $metrics['active_non_root_node_count'] > 0
                    ? 'go'
                    : 'hold',
                'detail' => 'The tenant must be active and the hierarchy must extend'
                    . ' beyond the root company node.',
            ],
            [
                'title' => 'Assignment-ready hierarchy reached within pilot target',
                'owner' => 'Product',
                'status' => $setupDurationStatus,
                'detail' => $metrics['onboarding_duration_hours'] !== null
                    ? sprintf('Measured onboarding duration is %.2f hours.', $metrics['onboarding_duration_hours'])
                    : 'Duration will appear after the first active team node is created.',
            ],
            [
                'title' => 'Hierarchy integrity stays within beta threshold',
                'owner' => 'Engineering',
                'status' => $errorRateStatus,
                'detail' => sprintf(
                    'Current hierarchy integrity error rate is %.2f%% against a <= 2.00%% pilot target.',
                    $metrics['hierarchy_integrity_error_rate'],
                ),
            ],
            [
                'title' => 'Customer-facing team can follow the same onboarding motion',
                'owner' => 'Customer Success',
                'status' => $assignmentStatus,
                'detail' => 'Use the playbook below to run the same provision-import-validate'
                    . ' workflow for each design partner.',
            ],
        ];
    }

    /**
     * @return list<array{
     *     phase: string,
     *     owner: string,
     *     guidance: string
     * }>
     */
    private function buildPlaybook(): array
    {
        return [
            [
                'phase' => '1. Provision',
                'owner' => 'Engineering',
                'guidance' => 'Create the tenant shell, confirm the root company name,'
                    . ' and set the hierarchy depth limit before inviting the pilot admin.',
            ],
            [
                'phase' => '2. Shape the hierarchy',
                'owner' => 'Customer Success',
                'guidance' => 'Choose the closest starter template, run the CSV dry-run,'
                    . ' fix any blocking validation issues, then commit the approved hierarchy.',
            ],
            [
                'phase' => '3. Validate readiness',
                'owner' => 'Product',
                'guidance' => 'Check the onboarding duration, confirm the hierarchy integrity'
                    . ' error rate stays below the pilot threshold, and capture blockers.',
            ],
            [
                'phase' => '4. Beta sign-off',
                'owner' => 'Shared',
                'guidance' => 'Engineering confirms tenant health, Customer Success confirms'
                    . ' the onboarding script is repeatable, and Product signs off on go/no-go.',
            ],
        ];
    }

    /**
     * @return list<array{
     *     key: string,
     *     name: string,
     *     description: string,
     *     row_count: int,
     *     download_url: string
     * }>
     */
    private function buildTemplates(): array
    {
        return array_map(
            static fn (array $template): array => [
                'key' => $template['key'],
                'name' => $template['name'],
                'description' => $template['description'],
                'row_count' => count($template['csv_rows']) - 1,
                'download_url' => route('tenancy.admin.org-nodes.imports.templates.show', [
                    'templateKey' => $template['key'],
                ]),
            ],
            $this->templateService->listTemplates(),
        );
    }
}
