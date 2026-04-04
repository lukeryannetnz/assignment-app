<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Http\Controllers;

use App\Domains\Tenancy\Data\OrgNodeType;
use App\Domains\Tenancy\Services\OrganizationHierarchyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Nette\ArgumentOutOfRangeException;

class OrganizationNodeController
{
    public function __construct(private readonly OrganizationHierarchyService $hierarchyService)
    {
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

        return view('tenancy::admin.org-nodes.index', [
            'nodes' => $nodes,
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
}
