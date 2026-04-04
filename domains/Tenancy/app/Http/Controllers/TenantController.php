<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Http\Controllers;

use App\Domains\Tenancy\Data\PlanTier;
use App\Domains\Tenancy\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Nette\ArgumentOutOfRangeException;

class TenantController
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function show(): JsonResponse
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

        $planTier = PlanTier::from((string) $tenant->plan_tier);

        return response()->json([
            'id' => (int) $tenant->id,
            'name' => (string) $tenant->name,
            'status' => (string) $tenant->status,
            'plan_tier' => $planTier->value,
            'hierarchy_depth_limit' => (int) $tenant->hierarchy_depth_limit,
        ]);
    }

    public function update(Request $request): JsonResponse
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
        ]);

        if ($payload === []) {
            throw ValidationException::withMessages([
                'tenant' => 'At least one tenant field must be provided.',
            ]);
        }

        $now = now();

        DB::table('tenants')
            ->where('id', $tenantId)
            ->update(array_merge($payload, ['updated_at' => $now]));

        DB::insert(
            'INSERT INTO tenant_audit_logs
                (tenant_id, actor_user_id, action, auditable_type, auditable_id, metadata, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $tenantId,
                $user->id,
                'tenant_updated',
                'tenant',
                $tenantId,
                json_encode($payload),
                $now,
                $now,
            ],
        );

        return $this->show();
    }
}
