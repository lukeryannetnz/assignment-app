<x-skills::app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-700">Admin: Skills</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Role-to-Skill Mapping</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Build role expectations in a simple order: load or create roles and skills, choose a role, save its draft, then publish when it is ready for manager visibility.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Bands</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ count($proficiencyBands) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Roles</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ count($workspace['roles']) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Published</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $workspace['published_count'] }}</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="bg-slate-100 py-10 text-slate-900">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('skills::partials.flash-messages')

            <section class="rounded-3xl border border-sky-200 bg-sky-50 p-6">
                <h3 class="text-base font-semibold text-sky-950">How this page works</h3>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-sky-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Step 1</p>
                        <p class="mt-2 font-medium text-slate-900">Prepare your library</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Load the starter library or add your own roles and skills.</p>
                    </div>
                    <div class="rounded-2xl border border-sky-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Step 2</p>
                        <p class="mt-2 font-medium text-slate-900">Select one role</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Use the role list to choose the role you want to edit. The draft editor updates for that role only.</p>
                    </div>
                    <div class="rounded-2xl border border-sky-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Step 3</p>
                        <p class="mt-2 font-medium text-slate-900">Save draft, then publish</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">The published panel shows the manager-visible version. It does not update until you publish.</p>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <div class="space-y-6">
                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="max-w-2xl">
                                <h3 class="text-xl font-semibold text-slate-900">Step 1: Role And Skill Library</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Start here if the tenant needs a baseline. The starter library adds software development and product management roles and skills.
                                </p>
                            </div>

                            <form method="POST" action="{{ route('skills.admin.role-mappings.starter-library.store') }}">
                                @csrf
                                <button type="submit" class="rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-500">
                                    Load Starter Library
                                </button>
                            </form>
                        </div>

                        <div class="mt-6 grid gap-6 lg:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                <h4 class="text-base font-semibold text-slate-900">Add Role</h4>
                                <form method="POST" action="{{ route('skills.admin.role-mappings.roles.store') }}" class="mt-4 space-y-4">
                                    @csrf
                                    <div>
                                        <label for="role_name" class="block text-sm font-medium text-slate-700">Role Name</label>
                                        <input id="role_name" name="name" type="text" value="{{ old('name') }}" class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    </div>
                                    <div>
                                        <label for="role_family" class="block text-sm font-medium text-slate-700">Role Family</label>
                                        <input id="role_family" name="role_family" type="text" value="{{ old('role_family') }}" class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    </div>
                                    <div>
                                        <label for="role_description" class="block text-sm font-medium text-slate-700">Description</label>
                                        <textarea id="role_description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('description') }}</textarea>
                                    </div>
                                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                        Create Role
                                    </button>
                                </form>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                <h4 class="text-base font-semibold text-slate-900">Add Skill</h4>
                                <form method="POST" action="{{ route('skills.admin.role-mappings.skills.store') }}" class="mt-4 space-y-4">
                                    @csrf
                                    @if ($workspace['selected_role_id'] !== null)
                                        <input type="hidden" name="role" value="{{ $workspace['selected_role_id'] }}">
                                    @endif
                                    <div>
                                        <label for="skill_name" class="block text-sm font-medium text-slate-700">Skill Name</label>
                                        <input id="skill_name" name="name" type="text" value="{{ old('name') }}" class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    </div>
                                    <div>
                                        <label for="skill_family" class="block text-sm font-medium text-slate-700">Skill Family</label>
                                        <input id="skill_family" name="skill_family" type="text" value="{{ old('skill_family') }}" class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    </div>
                                    <div>
                                        <label for="skill_description" class="block text-sm font-medium text-slate-700">Description</label>
                                        <textarea id="skill_description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('description') }}</textarea>
                                    </div>
                                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                        Add Skill
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4">
                            @foreach ($workspace['starter_families'] as $family)
                                <article class="rounded-2xl border border-slate-200 bg-white p-5">
                                    <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                                        <div>
                                            <h4 class="text-lg font-semibold text-slate-900">{{ $family['family'] }}</h4>
                                            <p class="mt-1 text-sm text-slate-600">{{ $family['description'] }}</p>
                                        </div>
                                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ count($family['roles']) }} starter roles</p>
                                    </div>

                                    <div class="mt-4 grid gap-4">
                                        @foreach ($family['roles'] as $role)
                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h5 class="text-base font-semibold text-slate-900">{{ $role['name'] }}</h5>
                                                    <span class="rounded-full {{ $role['status'] === 'Published' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }} px-2.5 py-1 text-xs font-medium">
                                                        {{ $role['status'] }}
                                                    </span>
                                                    <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700">
                                                        {{ $role['published_version'] }}
                                                    </span>
                                                </div>

                                                <p class="mt-2 text-sm text-slate-600">{{ $role['description'] }}</p>

                                                <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
                                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                                        <thead class="bg-slate-100 text-xs uppercase tracking-wide text-slate-500">
                                                            <tr>
                                                                <th class="px-4 py-3 text-left">Skill</th>
                                                                <th class="px-4 py-3 text-left">Importance</th>
                                                                <th class="px-4 py-3 text-left">Target Band</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-slate-200 bg-white">
                                                            @foreach ($role['skills'] as $skill)
                                                                <tr>
                                                                    <td class="px-4 py-3 text-slate-900">{{ $skill['name'] }}</td>
                                                                    <td class="px-4 py-3 text-slate-600">{{ ucfirst($skill['importance']) }}</td>
                                                                    <td class="px-4 py-3 text-slate-600">{{ $skill['target_proficiency'] }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-xl font-semibold text-slate-900">Step 2: Select A Role</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    The selected role controls both the draft editor and the published comparison below.
                                </p>
                            </div>
                            @if ($workspace['selected_role_name'] !== null)
                                <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-800">
                                    Selected: {{ $workspace['selected_role_name'] }}
                                </span>
                            @endif
                        </div>

                        <div class="mt-4 space-y-3">
                            @forelse ($workspace['roles'] as $role)
                                <a
                                    href="{{ route('skills.admin.role-mappings.index', ['role' => $role['id']]) }}"
                                    class="block rounded-2xl border px-4 py-3 transition {{ $workspace['selected_role_id'] === $role['id'] ? 'border-sky-300 bg-sky-50' : 'border-slate-200 bg-white hover:bg-slate-50' }}"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $role['name'] }}</p>
                                            <p class="mt-1 text-sm text-slate-600">{{ $role['family'] ?? 'Unassigned family' }}</p>
                                        </div>
                                        <span class="rounded-full {{ $role['has_published_mapping'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }} px-2.5 py-1 text-xs font-medium">
                                            {{ $role['has_published_mapping'] ? 'Published' : 'Draft only' }}
                                        </span>
                                    </div>
                                </a>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500">
                                    No roles exist yet. Load the starter library or add a role first.
                                </div>
                            @endforelse
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h3 class="text-xl font-semibold text-slate-900">Step 3: Edit Draft</h3>
                                @if ($workspace['selected_role_name'] !== null)
                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        Changes here affect the draft for <span class="font-semibold text-slate-900">{{ $workspace['selected_role_name'] }}</span>.
                                        Managers will not see these edits until you publish.
                                    </p>
                                @else
                                    <p class="mt-2 text-sm leading-6 text-slate-600">Select a role first to edit its draft mapping.</p>
                                @endif
                            </div>
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-800">
                                Draft only
                            </span>
                        </div>

                        @if ($workspace['selected_role_id'] !== null)
                            <form method="POST" action="{{ route('skills.admin.role-mappings.update', $workspace['selected_role_id']) }}" class="mt-6 space-y-5">
                                @csrf
                                @method('PUT')

                                <div>
                                    <label for="summary" class="block text-sm font-medium text-slate-700">Role Summary</label>
                                    <textarea id="summary" name="summary" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('summary', $workspace['draft_summary']) }}</textarea>
                                </div>

                                <div class="space-y-4">
                                    @for ($index = 0; $index < max(3, count($workspace['draft_skills'])); $index++)
                                        @php($draftSkill = $workspace['draft_skills'][$index] ?? null)
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                            <div class="grid gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700">Skill</label>
                                                    <select name="skills[{{ $index }}][skill_id]" class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                                        <option value="">Select a skill</option>
                                                        @foreach ($workspace['skills'] as $skill)
                                                            <option value="{{ $skill['id'] }}" @selected((int) old("skills.$index.skill_id", $draftSkill['skill_id'] ?? 0) === $skill['id'])>
                                                                {{ $skill['name'] }}@if ($skill['family'] !== null) · {{ $skill['family'] }}@endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="grid gap-4 md:grid-cols-2">
                                                    <div>
                                                        <label class="block text-sm font-medium text-slate-700">Importance</label>
                                                        <select name="skills[{{ $index }}][importance]" class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                                            <option value="">Select importance</option>
                                                            @foreach ($importanceWeights as $importance)
                                                                <option value="{{ $importance['value'] }}" @selected(old("skills.$index.importance", $draftSkill['importance'] ?? '') === $importance['value'])>
                                                                    {{ $importance['label'] }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium text-slate-700">Target Proficiency</label>
                                                        <select name="skills[{{ $index }}][target_proficiency]" class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                                            <option value="">Select band</option>
                                                            @foreach ($proficiencyBands as $band)
                                                                <option value="{{ $band['value'] }}" @selected(old("skills.$index.target_proficiency", $draftSkill['target_proficiency'] ?? '') === $band['value'])>
                                                                    {{ $band['label'] }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700">Rationale Note</label>
                                                    <textarea name="skills[{{ $index }}][rationale_note]" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old("skills.$index.rationale_note", $draftSkill['rationale_note'] ?? '') }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Publishing Rules</p>
                                    <ul class="mt-3 space-y-2 text-sm text-slate-600">
                                        <li>Every published skill needs a target proficiency band.</li>
                                        <li>At least one `critical` or `core` skill is required before publish.</li>
                                        <li>Duplicate skills are blocked in the same role mapping.</li>
                                    </ul>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                        Save Draft
                                    </button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('skills.admin.role-mappings.publish', $workspace['selected_role_id']) }}" class="mt-3">
                                @csrf
                                <button type="submit" class="rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-500">
                                    Publish Mapping
                                </button>
                            </form>
                        @else
                            <div class="mt-6 rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500">
                                Select a role from Step 2 to begin editing.
                            </div>
                        @endif
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-xl font-semibold text-slate-900">Published Version Managers See</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            This panel is the live, read-only version for managers. It changes only after you publish the draft above.
                        </p>

                        @if ($workspace['published_version'] !== null)
                            <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                                <p class="text-sm font-semibold text-slate-900">Version v{{ $workspace['published_version']['version_number'] }}</p>
                                <p class="mt-1 text-sm text-slate-600">
                                    Published {{ $workspace['published_version']['published_at'] }}
                                    @if ($workspace['published_version']['published_by_name'] !== null)
                                        by {{ $workspace['published_version']['published_by_name'] }}
                                    @endif
                                </p>
                                @if ($workspace['published_version']['summary'] !== null)
                                    <p class="mt-3 text-sm text-slate-700">{{ $workspace['published_version']['summary'] }}</p>
                                @endif
                            </div>

                            <div class="mt-4 space-y-3">
                                @foreach ($workspace['published_version']['skills'] as $skill)
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-medium text-slate-900">{{ $skill['skill_name'] }}</p>
                                            <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-medium text-slate-700">
                                                {{ $skill['importance_label'] }}
                                            </span>
                                            <span class="rounded-full bg-sky-100 px-2 py-1 text-xs font-medium text-sky-800">
                                                {{ $skill['target_proficiency_label'] }}
                                            </span>
                                        </div>
                                        @if ($skill['rationale_note'] !== null)
                                            <p class="mt-2 text-sm text-slate-600">{{ $skill['rationale_note'] }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-4 rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500">
                                No published mapping exists for this role yet.
                            </div>
                        @endif
                    </section>
                </div>
            </section>
        </div>
    </div>
</x-skills::app-layout>
