<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Http\Controllers;

use App\Domains\Tenancy\Data\OrgNodeType;
use App\Domains\Tenancy\Services\OrganizationHierarchyService;
use App\Domains\Tenancy\Support\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Nette\ArgumentOutOfRangeException;

class OrganizationNodeController
{
    public function __construct(
        private readonly OrganizationHierarchyService $hierarchyService,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function index(Request $request): JsonResponse|View
    {
        $nodes = $this->hierarchyService->listNodes();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $nodes,
            ]);
        }

        /** @var array<string, mixed>|null $importPreview */
        $importPreview = $request->session()->get('tenancy.org_import.preview');
        /** @var array<string, mixed>|null $importResult */
        $importResult = $request->session()->get('tenancy.org_import.result');
        $hierarchyDepthLimit = $this->currentTenantDepthLimit();

        return view('tenancy::admin.org-nodes.index', [
            'nodes' => $nodes,
            'createParentOptions' => array_values(array_filter(
                $nodes,
                static fn (array $node): bool => $node['depth'] + 1 < $hierarchyDepthLimit,
            )),
            'validMoveParentOptions' => $this->buildValidMoveParentOptions($nodes, $hierarchyDepthLimit),
            'hierarchyDepthLimit' => $hierarchyDepthLimit,
            'nodeTypes' => array_values(array_filter(
                OrgNodeType::values(),
                static fn (string $nodeType): bool => $nodeType !== OrgNodeType::Company->value,
            )),
            'importPreview' => $importPreview,
            'importResult' => $importResult,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
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

        if (!$this->shouldReturnHtml($request)) {
            return response()->json(['data' => $node], 201);
        }

        return redirect()
            ->route('tenancy.admin.org-nodes.index')
            ->with('status', sprintf('Organization node "%s" created.', $node['name']));
    }

    public function update(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $node = $this->hierarchyService->updateNode($id, $validated, (int) $user->id);

        if (!$this->shouldReturnHtml($request)) {
            return response()->json(['data' => $node]);
        }

        return redirect()
            ->route('tenancy.admin.org-nodes.index')
            ->with('status', sprintf('Organization node renamed to "%s".', $node['name']));
    }

    public function move(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        $validated = $request->validate([
            'parent_id' => 'required|integer|min:1',
        ]);

        $node = $this->hierarchyService->moveNode($id, (int) $validated['parent_id'], (int) $user->id);

        if (!$this->shouldReturnHtml($request)) {
            return response()->json(['data' => $node]);
        }

        return redirect()
            ->route('tenancy.admin.org-nodes.index')
            ->with('status', sprintf('Organization node "%s" moved.', $node['name']));
    }

    public function deactivate(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        $node = $this->hierarchyService->deactivateNode($id, (int) $user->id);

        if (!$this->shouldReturnHtml($request)) {
            return response()->json(['data' => $node]);
        }

        return redirect()
            ->route('tenancy.admin.org-nodes.index')
            ->with('status', sprintf('Organization node "%s" deactivated.', $node['name']));
    }

    public function reactivate(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        $node = $this->hierarchyService->reactivateNode($id, (int) $user->id);

        if (!$this->shouldReturnHtml($request)) {
            return response()->json(['data' => $node]);
        }

        return redirect()
            ->route('tenancy.admin.org-nodes.index')
            ->with('status', sprintf('Organization node "%s" reactivated.', $node['name']));
    }

    private function shouldReturnHtml(Request $request): bool
    {
        return $request->string('ui_form')->toString() === '1';
    }

    private function currentTenantDepthLimit(): int
    {
        $tenantId = $this->tenantContext->requireTenantId();

        /** @var object{hierarchy_depth_limit: int}|null $tenant */
        $tenant = DB::selectOne(
            'SELECT hierarchy_depth_limit
             FROM tenants
             WHERE id = ?
             LIMIT 1',
            [$tenantId],
        );

        if ($tenant === null) {
            throw new ArgumentOutOfRangeException('Tenant is required.');
        }

        return (int) $tenant->hierarchy_depth_limit;
    }

    /**
     * @param  list<array{
     *     id: int,
     *     tenant_id: int,
     *     parent_id: int|null,
     *     node_type: string,
     *     name: string,
     *     depth: int,
     *     is_active: bool
     * }>  $nodes
     * @return array<int, list<array{
     *     id: int,
     *     tenant_id: int,
     *     parent_id: int|null,
     *     node_type: string,
     *     name: string,
     *     depth: int,
     *     is_active: bool
     * }>>
     */
    private function buildValidMoveParentOptions(array $nodes, int $hierarchyDepthLimit): array
    {
        /**
         * @var array<int, array{
         *     id: int,
         *     tenant_id: int,
         *     parent_id: int|null,
         *     node_type: string,
         *     name: string,
         *     depth: int,
         *     is_active: bool
         * }> $nodesById
         */
        $nodesById = [];
        foreach ($nodes as $node) {
            $nodesById[$node['id']] = $node;
        }

        /** @var array<int, list<int>> $descendantIdsByNode */
        $descendantIdsByNode = [];
        /** @var array<int, int> $maxSubtreeOffsetByNode */
        $maxSubtreeOffsetByNode = [];

        foreach ($nodes as $node) {
            $descendantIdsByNode[$node['id']] = [];
            $maxSubtreeOffsetByNode[$node['id']] = 0;

            foreach ($nodes as $candidateDescendant) {
                if ($candidateDescendant['id'] === $node['id']) {
                    continue;
                }

                $currentParentId = $candidateDescendant['parent_id'];
                while ($currentParentId !== null) {
                    if ($currentParentId === $node['id']) {
                        $descendantIdsByNode[$node['id']][] = $candidateDescendant['id'];
                        $maxSubtreeOffsetByNode[$node['id']] = max(
                            $maxSubtreeOffsetByNode[$node['id']],
                            $candidateDescendant['depth'] - $node['depth'],
                        );
                        break;
                    }

                    $currentParent = $nodesById[$currentParentId] ?? null;
                    $currentParentId = is_array($currentParent) ? $currentParent['parent_id'] : null;
                }
            }
        }

        /**
         * @var array<int, list<array{
         *     id: int,
         *     tenant_id: int,
         *     parent_id: int|null,
         *     node_type: string,
         *     name: string,
         *     depth: int,
         *     is_active: bool
         * }>> $validMoveParentOptions
         */
        $validMoveParentOptions = [];
        foreach ($nodes as $node) {
            $validMoveParentOptions[$node['id']] = array_values(array_filter(
                $nodes,
                function (array $candidateParent) use (
                    $descendantIdsByNode,
                    $hierarchyDepthLimit,
                    $maxSubtreeOffsetByNode,
                    $node,
                ): bool {
                    if ($candidateParent['id'] === $node['id']) {
                        return false;
                    }

                    if (in_array($candidateParent['id'], $descendantIdsByNode[$node['id']], true)) {
                        return false;
                    }

                    return $candidateParent['depth'] + 1 + $maxSubtreeOffsetByNode[$node['id']]
                        < $hierarchyDepthLimit;
                },
            ));
        }

        return $validMoveParentOptions;
    }
}
