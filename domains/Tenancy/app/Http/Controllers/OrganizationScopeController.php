<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Http\Controllers;

use App\Domains\Tenancy\Data\OrgNodeType;
use App\Domains\Tenancy\Services\OrganizationScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nette\ArgumentOutOfRangeException;

class OrganizationScopeController
{
    public function __construct(private readonly OrganizationScopeService $scopeService)
    {
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->requireUser($request);

        return response()->json([
            'data' => $this->scopeService->resolveNodeScope($id),
        ]);
    }

    public function showBoundary(Request $request, int $id, string $scopeType): JsonResponse
    {
        $this->requireUser($request);

        return response()->json([
            'data' => $this->scopeService->resolveScopedBoundary($id, OrgNodeType::from($scopeType)),
        ]);
    }

    private function requireUser(Request $request): void
    {
        if ($request->user() === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }
    }
}
