<?php

declare(strict_types=1);

namespace App\Domains\Skills\Http\Controllers;

use App\Domains\Skills\Data\ProficiencyBand;
use App\Domains\Skills\Data\PublishedRoleMappingData;
use App\Domains\Skills\Data\RoleData;
use App\Domains\Skills\Data\RoleMappingSkillData;
use App\Domains\Skills\Data\RoleMappingVersionData;
use App\Domains\Skills\Data\RoleMappingWorkspaceData;
use App\Domains\Skills\Data\SkillData;
use App\Domains\Skills\Data\SkillImportance;
use App\Domains\Skills\Services\RoleMappingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Nette\ArgumentOutOfRangeException;

class RoleMappingController
{
    public function __construct(
        private readonly RoleMappingService $roleMappingService,
    ) {
    }

    public function adminIndex(Request $request): View
    {
        $workspace = $this->roleMappingService->buildWorkspace($this->roleIdFromRequest($request));

        return view('skills::admin.role-mappings.index', [
            'proficiencyBands' => $this->proficiencyBands(),
            'importanceWeights' => $this->importanceWeights(),
            'workspace' => $this->workspaceToArray($workspace),
        ]);
    }

    public function loadStarterLibrary(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        $this->roleMappingService->seedStarterLibrary((int) $user->id);

        return redirect()
            ->route('skills.admin.role-mappings.index')
            ->with('status', 'Starter library loaded for software development and product management roles.');
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role_family' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        $roleId = $this->roleMappingService->createRole(
            name: (string) $validated['name'],
            roleFamily: isset($validated['role_family']) ? (string) $validated['role_family'] : null,
            description: isset($validated['description']) ? (string) $validated['description'] : null,
            actorUserId: (int) $user->id,
        );

        return redirect()
            ->route('skills.admin.role-mappings.index', ['role' => $roleId])
            ->with('status', sprintf('Role "%s" is ready for mapping.', $validated['name']));
    }

    public function storeSkill(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'skill_family' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        $this->roleMappingService->createSkill(
            name: (string) $validated['name'],
            skillFamily: isset($validated['skill_family']) ? (string) $validated['skill_family'] : null,
            description: isset($validated['description']) ? (string) $validated['description'] : null,
        );

        $parameters = [];
        $roleId = $this->roleIdFromRequest($request);
        if ($roleId !== null) {
            $parameters['role'] = $roleId;
        }

        return redirect()
            ->route('skills.admin.role-mappings.index', $parameters)
            ->with('status', sprintf('Skill "%s" added to the tenant library.', $validated['name']));
    }

    public function update(Request $request, int $roleId): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        $validated = $request->validate([
            'summary' => 'nullable|string|max:2000',
            'skills' => 'required|array|min:1',
            'skills.*.skill_id' => 'nullable|integer|min:1',
            'skills.*.importance' => ['nullable', Rule::enum(SkillImportance::class)],
            'skills.*.target_proficiency' => ['nullable', Rule::enum(ProficiencyBand::class)],
            'skills.*.rationale_note' => 'nullable|string|max:1000',
        ]);

        /** @var list<array{
         *     skill_id: int,
         *     importance: SkillImportance,
         *     target_proficiency: ProficiencyBand,
         *     rationale_note: string|null
         * }> $rows
         */
        $rows = [];
        foreach ($validated['skills'] as $row) {
            $hasAnyValue = ($row['skill_id'] ?? null) !== null
                || ($row['importance'] ?? null) !== null
                || ($row['target_proficiency'] ?? null) !== null
                || trim((string) ($row['rationale_note'] ?? '')) !== '';

            if (!$hasAnyValue) {
                continue;
            }

            if (
                ($row['skill_id'] ?? null) === null
                || ($row['importance'] ?? null) === null
                || ($row['target_proficiency'] ?? null) === null
            ) {
                return back()
                    ->withErrors(['skills' => 'Each mapped skill needs a skill, importance, and proficiency band.'])
                    ->withInput();
            }

            $rows[] = [
                'skill_id' => (int) $row['skill_id'],
                'importance' => SkillImportance::from((string) $row['importance']),
                'target_proficiency' => ProficiencyBand::from((string) $row['target_proficiency']),
                'rationale_note' => isset($row['rationale_note']) ? (string) $row['rationale_note'] : null,
            ];
        }

        $this->roleMappingService->saveDraft(
            roleId: $roleId,
            summary: isset($validated['summary']) ? (string) $validated['summary'] : null,
            rows: $rows,
            actorUserId: (int) $user->id,
        );

        return redirect()
            ->route('skills.admin.role-mappings.index', ['role' => $roleId])
            ->with('status', 'Draft mapping saved.');
    }

    public function publish(Request $request, int $roleId): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        $this->roleMappingService->publish($roleId, (int) $user->id);

        return redirect()
            ->route('skills.admin.role-mappings.index', ['role' => $roleId])
            ->with('status', 'Role mapping published.');
    }

    public function managerIndex(Request $request): View
    {
        $selectedRoleId = $this->roleIdFromRequest($request);
        $publishedMappings = $this->roleMappingService->listPublishedMappings();
        $selectedMapping = $this->roleMappingService->publishedMapping($selectedRoleId);

        return view('skills::manager.role-mappings.index', [
            'proficiencyBands' => $this->proficiencyBands(),
            'publishedMappings' => array_map(
                fn (PublishedRoleMappingData $mapping): array => $this->publishedMappingToArray($mapping),
                $publishedMappings,
            ),
            'selectedMapping' => $selectedMapping !== null ? $this->publishedMappingToArray($selectedMapping) : null,
        ]);
    }

    /**
     * @return list<array{value: string, label: string, description: string}>
     */
    private function proficiencyBands(): array
    {
        return array_map(
            static fn (ProficiencyBand $band): array => [
                'value' => $band->value,
                'label' => $band->label(),
                'description' => $band->description(),
            ],
            ProficiencyBand::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function importanceWeights(): array
    {
        return array_map(
            static fn (SkillImportance $importance): array => [
                'value' => $importance->value,
                'label' => $importance->label(),
            ],
            SkillImportance::cases(),
        );
    }

    private function roleIdFromRequest(Request $request): ?int
    {
        $roleId = $request->integer('role');

        return $roleId > 0 ? $roleId : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function workspaceToArray(RoleMappingWorkspaceData $workspace): array
    {
        return [
            'roles' => array_map(
                static fn (RoleData $role): array => $role->toArray(),
                $workspace->roles,
            ),
            'skills' => array_map(
                static fn (SkillData $skill): array => $skill->toArray(),
                $workspace->skills,
            ),
            'draft_skills' => array_map(
                static fn (RoleMappingSkillData $skill): array => $skill->toArray(),
                $workspace->draftSkills,
            ),
            'starter_families' => $workspace->starterFamilies,
            'published_count' => $workspace->publishedCount,
            'selected_role_id' => $workspace->selectedRoleId,
            'selected_role_name' => $workspace->selectedRoleName,
            'selected_role_family' => $workspace->selectedRoleFamily,
            'selected_role_description' => $workspace->selectedRoleDescription,
            'draft_summary' => $workspace->draftSummary,
            'published_version' => $workspace->publishedVersion !== null ? $workspace->publishedVersion->toArray() : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function publishedMappingToArray(PublishedRoleMappingData $mapping): array
    {
        return $mapping->toArray();
    }
}
