<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Http\Controllers;

use App\Domains\Tenancy\Data\PlanTier;
use App\Domains\Tenancy\Services\TenantRootCompanyService;
use App\Domains\Tenancy\Support\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Nette\ArgumentOutOfRangeException;

class TenantController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantRootCompanyService $tenantRootCompanyService,
    ) {
    }

    public function show(Request $request): JsonResponse|View
    {
        $tenant = $this->currentTenant();

        if ($request->expectsJson()) {
            return response()->json($tenant);
        }

        return view('tenancy::admin.tenant.show', [
            'tenant' => $tenant,
            'planTiers' => PlanTier::values(),
        ]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        $tenantId = $this->tenantContext->requireTenantId();

        $payload = $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,inactive',
            'plan_tier' => ['sometimes', Rule::enum(PlanTier::class)],
            'hierarchy_depth_limit' => 'sometimes|integer|min:1|max:8',
            'root_company_name' => 'sometimes|string|max:255',
        ]);

        if ($payload === []) {
            throw ValidationException::withMessages([
                'tenant' => 'At least one tenant field must be provided.',
            ]);
        }

        $now = now();

        $tenantPayload = $payload;
        unset($tenantPayload['root_company_name']);

        DB::transaction(function () use ($tenantId, $tenantPayload, $payload, $user, $now): void {
            if ($tenantPayload !== []) {
                DB::table('tenants')
                    ->where('id', $tenantId)
                    ->update(array_merge($tenantPayload, ['updated_at' => $now]));
            }

            if (array_key_exists('root_company_name', $payload)) {
                $rootCompanyName = $payload['root_company_name'];
                assert(is_string($rootCompanyName));

                $this->tenantRootCompanyService->renameRootCompanyNode(
                    tenantId: $tenantId,
                    name: $rootCompanyName,
                    actorUserId: (int) $user->id,
                );
            }

            if ($tenantPayload !== []) {
                DB::insert(
                    'INSERT INTO tenant_audit_logs
                        (tenant_id, actor_user_id, action, auditable_type,
                         auditable_id, metadata, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $tenantId,
                        $user->id,
                        'tenant_updated',
                        'tenant',
                        $tenantId,
                        json_encode($tenantPayload),
                        $now,
                        $now,
                    ],
                );
            }
        });

        if (!$this->shouldReturnHtml($request)) {
            return response()->json($this->currentTenant());
        }

        return redirect()
            ->route('tenancy.admin.tenant.show')
            ->with('status', 'Tenant settings updated.');
    }

    private function shouldReturnHtml(Request $request): bool
    {
        return $request->string('ui_form')->toString() === '1';
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     root_company_name: string,
     *     status: string,
     *     plan_tier: string,
     *     hierarchy_depth_limit: int
     * }
     */
    private function currentTenant(): array
    {
        $tenantId = $this->tenantContext->requireTenantId();

        /** @var object{id: int, name: string, status: string, plan_tier: string, hierarchy_depth_limit: int}|null $tenant */
        $tenant = DB::selectOne(
            'SELECT id, name, status, plan_tier, hierarchy_depth_limit FROM tenants WHERE id = ? LIMIT 1',
            [$tenantId],
        );

        if ($tenant === null) {
            abort(404, 'Tenant not found.');
        }

        $rootCompany = $this->tenantRootCompanyService->requireRootCompanyNode($tenantId);
        $planTier = PlanTier::from((string) $tenant->plan_tier);

        return [
            'id' => (int) $tenant->id,
            'name' => (string) $tenant->name,
            'root_company_name' => $rootCompany['name'],
            'status' => (string) $tenant->status,
            'plan_tier' => $planTier->value,
            'hierarchy_depth_limit' => (int) $tenant->hierarchy_depth_limit,
        ];
    }
}
