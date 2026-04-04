<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Http\Controllers;

use App\Domains\Tenancy\Data\PlanTier;
use App\Domains\Tenancy\Services\PlatformTenantProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Nette\ArgumentOutOfRangeException;
use Symfony\Component\HttpFoundation\Response;

class PlatformTenantProvisioningController
{
    public function __construct(private readonly PlatformTenantProvisioningService $provisioningService)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        if (!$user->isAdmin()) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized. Admin access required.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'plan_tier' => ['sometimes', Rule::enum(PlanTier::class)],
            'hierarchy_depth_limit' => ['sometimes', 'integer', 'min:1', 'max:8'],
            'root_org_name' => ['sometimes', 'string', 'max:255'],
        ]);

        $result = $this->provisioningService->provision($validated, (int) $user->id);

        return response()->json([
            'data' => $result,
        ], 201);
    }
}
