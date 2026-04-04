<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Http\Controllers;

use App\Domains\Tenancy\Data\OrgNodeType;
use App\Domains\Tenancy\Services\OrganizationHierarchyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            'node_type' => ['required', Rule::enum(OrgNodeType::class)],
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
}
