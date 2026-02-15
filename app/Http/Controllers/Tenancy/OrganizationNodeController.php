<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Services\Tenancy\OrganizationHierarchyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Nette\ArgumentOutOfRangeException;

class OrganizationNodeController
{
    public function __construct(private readonly OrganizationHierarchyService $hierarchyService)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->hierarchyService->listNodes(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'node_type' => 'required|in:company,business_unit,department,team',
            'parent_id' => 'nullable|integer|min:1',
        ]);

        $node = $this->hierarchyService->createNode($validated, (int) $user->id);

        return response()->json(['data' => $node], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $node = $this->hierarchyService->updateNode($id, $validated, (int) $user->id);

        return response()->json(['data' => $node]);
    }

    public function move(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        $validated = $request->validate([
            'parent_id' => 'required|integer|min:1',
        ]);

        $node = $this->hierarchyService->moveNode($id, (int) $validated['parent_id'], (int) $user->id);

        return response()->json(['data' => $node]);
    }

    public function deactivate(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        $node = $this->hierarchyService->deactivateNode($id, (int) $user->id);

        return response()->json(['data' => $node]);
    }

    public function reactivate(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        $node = $this->hierarchyService->reactivateNode($id, (int) $user->id);

        return response()->json(['data' => $node]);
    }

    public function scope(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'include_self' => 'sometimes|boolean',
            'active_only' => 'sometimes|boolean',
        ]);

        $nodeIds = $this->hierarchyService->resolveScopeForNode(
            $id,
            $request->boolean('include_self', true),
            $request->boolean('active_only', true),
        );

        return response()->json([
            'data' => [
                'root_node_id' => $id,
                'node_ids' => $nodeIds,
            ],
        ]);
    }

    public function resolveScope(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_ids' => 'required|array|min:1',
            'node_ids.*' => 'integer|min:1',
            'include_descendants' => 'sometimes|boolean',
            'include_self' => 'sometimes|boolean',
            'active_only' => 'sometimes|boolean',
        ]);

        $rawNodeIds = $validated['node_ids'];
        $nodeIds = [];
        foreach ($rawNodeIds as $rawNodeId) {
            if (!is_int($rawNodeId)) {
                throw ValidationException::withMessages([
                    'node_ids' => 'Each node ID must be an integer.',
                ]);
            }

            $nodeIds[] = $rawNodeId;
        }

        $resolvedNodeIds = $this->hierarchyService->resolveScopeForNodes(
            $nodeIds,
            $request->boolean('include_descendants', true),
            $request->boolean('include_self', true),
            $request->boolean('active_only', true),
        );

        return response()->json([
            'data' => [
                'node_ids' => $resolvedNodeIds,
            ],
        ]);
    }
}
