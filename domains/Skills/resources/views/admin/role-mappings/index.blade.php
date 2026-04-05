<x-skills::app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-300">Admin Workspace</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-white">Role-to-Skill Mapping</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
                    Create role expectations, publish stable mapping versions, and keep the source of truth ready for skill profiles and pathway recommendations.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Bands</p>
                    <p class="mt-1 text-lg font-semibold text-white">{{ count($proficiencyBands) }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Roles</p>
                    <p class="mt-1 text-lg font-semibold text-white">{{ count($workspace['roles']) }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Published</p>
                    <p class="mt-1 text-lg font-semibold text-white">{{ $workspace['published_count'] }}</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('skills::partials.flash-messages')

            <section class="grid gap-6 lg:grid-cols-[1.15fr_1fr]">
                <div class="space-y-6">
                    <section class="rounded-3xl border border-white/10 bg-white/5 p-6 shadow-2xl shadow-cyan-950/20">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h3 class="text-xl font-semibold text-white">Curated Starter Library</h3>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
                                    Load software development and product management starter mappings when a pilot tenant needs a useful baseline fast.
                                </p>
                            </div>

                            <form method="POST" action="{{ route('skills.admin.role-mappings.starter-library.store') }}">
                                @csrf
                                <button type="submit" class="rounded-xl bg-cyan-500 px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400">
                                    Load Starter Library
                                </button>
                            </form>
                        </div>

                        <div class="mt-6 grid gap-6">
                            @foreach ($workspace['starter_families'] as $family)
                                <article class="rounded-2xl border border-white/10 bg-slate-900/80 p-5">
                                    <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                                        <div>
                                            <h4 class="text-lg font-semibold text-white">{{ $family['family'] }}</h4>
                                            <p class="mt-1 text-sm text-slate-400">{{ $family['description'] }}</p>
                                        </div>
                                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">{{ count($family['roles']) }} starter roles</p>
                                    </div>

                                    <div class="mt-4 grid gap-4">
                                        @foreach ($family['roles'] as $role)
                                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h5 class="text-base font-semibold text-white">{{ $role['name'] }}</h5>
                                                    <span class="rounded-full {{ $role['status'] === 'Published' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-500/15 text-amber-300' }} px-2.5 py-1 text-xs font-medium">
                                                        {{ $role['status'] }}
                                                    </span>
                                                    <span class="rounded-full bg-white/10 px-2.5 py-1 text-xs font-medium text-slate-300">
                                                        {{ $role['published_version'] }}
                                                    </span>
                                                </div>

                                                <p class="mt-2 text-sm text-slate-400">{{ $role['description'] }}</p>

                                                <div class="mt-4 overflow-hidden rounded-xl border border-white/10">
                                                    <table class="min-w-full divide-y divide-white/10 text-sm">
                                                        <thead class="bg-white/5 text-xs uppercase tracking-wide text-slate-400">
                                                            <tr>
                                                                <th class="px-4 py-3 text-left">Skill</th>
                                                                <th class="px-4 py-3 text-left">Importance</th>
                                                                <th class="px-4 py-3 text-left">Target Band</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-white/10 bg-slate-950/40">
                                                            @foreach ($role['skills'] as $skill)
                                                                <tr>
                                                                    <td class="px-4 py-3 text-slate-100">{{ $skill['name'] }}</td>
                                                                    <td class="px-4 py-3 text-slate-300">{{ ucfirst($skill['importance']) }}</td>
                                                                    <td class="px-4 py-3 text-slate-300">{{ $skill['target_proficiency'] }}</td>
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

                    <section class="grid gap-6 lg:grid-cols-2">
                        <div class="rounded-3xl border border-white/10 bg-slate-900/90 p-6">
                            <h3 class="text-lg font-semibold text-white">Add Role</h3>
                            <form method="POST" action="{{ route('skills.admin.role-mappings.roles.store') }}" class="mt-4 space-y-4">
                                @csrf
                                <div>
                                    <label for="role_name" class="block text-sm font-medium text-slate-200">Role Name</label>
                                    <input id="role_name" name="name" type="text" value="{{ old('name') }}" class="mt-1 block w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white shadow-sm focus:border-cyan-400 focus:ring-cyan-400">
                                </div>
                                <div>
                                    <label for="role_family" class="block text-sm font-medium text-slate-200">Role Family</label>
                                    <input id="role_family" name="role_family" type="text" value="{{ old('role_family') }}" class="mt-1 block w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white shadow-sm focus:border-cyan-400 focus:ring-cyan-400">
                                </div>
                                <div>
                                    <label for="role_description" class="block text-sm font-medium text-slate-200">Description</label>
                                    <textarea id="role_description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white shadow-sm focus:border-cyan-400 focus:ring-cyan-400">{{ old('description') }}</textarea>
                                </div>
                                <button type="submit" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-slate-100">
                                    Create Role
                                </button>
                            </form>
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-slate-900/90 p-6">
                            <h3 class="text-lg font-semibold text-white">Add Skill</h3>
                            <form method="POST" action="{{ route('skills.admin.role-mappings.skills.store') }}" class="mt-4 space-y-4">
                                @csrf
                                @if ($workspace['selected_role_id'] !== null)
                                    <input type="hidden" name="role" value="{{ $workspace['selected_role_id'] }}">
                                @endif
                                <div>
                                    <label for="skill_name" class="block text-sm font-medium text-slate-200">Skill Name</label>
                                    <input id="skill_name" name="name" type="text" value="{{ old('name') }}" class="mt-1 block w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white shadow-sm focus:border-cyan-400 focus:ring-cyan-400">
                                </div>
                                <div>
                                    <label for="skill_family" class="block text-sm font-medium text-slate-200">Skill Family</label>
                                    <input id="skill_family" name="skill_family" type="text" value="{{ old('skill_family') }}" class="mt-1 block w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white shadow-sm focus:border-cyan-400 focus:ring-cyan-400">
                                </div>
                                <div>
                                    <label for="skill_description" class="block text-sm font-medium text-slate-200">Description</label>
                                    <textarea id="skill_description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white shadow-sm focus:border-cyan-400 focus:ring-cyan-400">{{ old('description') }}</textarea>
                                </div>
                                <button type="submit" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-slate-100">
                                    Add Skill
                                </button>
                            </form>
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
                    <section class="rounded-3xl border border-white/10 bg-slate-900/90 p-6">
                        <h3 class="text-xl font-semibold text-white">Roles</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-300">Pick a role to edit its draft mapping and compare against the current published version.</p>

                        <div class="mt-4 space-y-3">
                            @forelse ($workspace['roles'] as $role)
                                <a
                                    href="{{ route('skills.admin.role-mappings.index', ['role' => $role['id']]) }}"
                                    class="block rounded-2xl border px-4 py-3 transition {{ $workspace['selected_role_id'] === $role['id'] ? 'border-cyan-400/60 bg-cyan-500/10' : 'border-white/10 bg-white/5 hover:bg-white/10' }}"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-white">{{ $role['name'] }}</p>
                                            <p class="mt-1 text-sm text-slate-400">{{ $role['family'] ?? 'Unassigned family' }}</p>
                                        </div>
                                        <span class="rounded-full {{ $role['has_published_mapping'] ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-500/15 text-amber-300' }} px-2.5 py-1 text-xs font-medium">
                                            {{ $role['has_published_mapping'] ? 'Published' : 'Draft only' }}
                                        </span>
                                    </div>
                                </a>
                            @empty
                                <div class="rounded-2xl border border-dashed border-white/10 px-4 py-6 text-sm text-slate-400">
                                    No roles exist yet. Add a role or load the starter library first.
                                </div>
                            @endforelse
                        </div>
                    </section>

                    <section class="rounded-3xl border border-white/10 bg-slate-900/90 p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-xl font-semibold text-white">Draft Editor</h3>
                                @if ($workspace['selected_role_name'] !== null)
                                    <p class="mt-2 text-sm leading-6 text-slate-300">
                                        Editing <span class="font-semibold text-white">{{ $workspace['selected_role_name'] }}</span>
                                        @if ($workspace['selected_role_family'] !== null)
                                            in {{ $workspace['selected_role_family'] }}
                                        @endif
                                    </p>
                                @else
                                    <p class="mt-2 text-sm leading-6 text-slate-300">Select a role to edit its draft mapping.</p>
                                @endif
                            </div>
                            <span class="rounded-full bg-amber-500/15 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-300">
                                Draft
                            </span>
                        </div>

                        @if ($workspace['selected_role_id'] !== null)
                            <form method="POST" action="{{ route('skills.admin.role-mappings.update', $workspace['selected_role_id']) }}" class="mt-6 space-y-5">
                                @csrf
                                @method('PUT')

                                <div>
                                    <label for="summary" class="block text-sm font-medium text-slate-200">Role Summary</label>
                                    <textarea id="summary" name="summary" rows="3" class="mt-1 block w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white shadow-sm focus:border-cyan-400 focus:ring-cyan-400">{{ old('summary', $workspace['draft_summary']) }}</textarea>
                                </div>

                                <div class="space-y-4">
                                    @for ($index = 0; $index < max(3, count($workspace['draft_skills'])); $index++)
                                        @php($draftSkill = $workspace['draft_skills'][$index] ?? null)
                                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                            <div class="grid gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-slate-200">Skill</label>
                                                    <select name="skills[{{ $index }}][skill_id]" class="mt-1 block w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white shadow-sm focus:border-cyan-400 focus:ring-cyan-400">
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
                                                        <label class="block text-sm font-medium text-slate-200">Importance</label>
                                                        <select name="skills[{{ $index }}][importance]" class="mt-1 block w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white shadow-sm focus:border-cyan-400 focus:ring-cyan-400">
                                                            <option value="">Select importance</option>
                                                            @foreach ($importanceWeights as $importance)
                                                                <option value="{{ $importance['value'] }}" @selected(old("skills.$index.importance", $draftSkill['importance'] ?? '') === $importance['value'])>
                                                                    {{ $importance['label'] }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium text-slate-200">Target Proficiency</label>
                                                        <select name="skills[{{ $index }}][target_proficiency]" class="mt-1 block w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white shadow-sm focus:border-cyan-400 focus:ring-cyan-400">
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
                                                    <label class="block text-sm font-medium text-slate-200">Rationale Note</label>
                                                    <textarea name="skills[{{ $index }}][rationale_note]" rows="2" class="mt-1 block w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white shadow-sm focus:border-cyan-400 focus:ring-cyan-400">{{ old("skills.$index.rationale_note", $draftSkill['rationale_note'] ?? '') }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <p class="text-xs uppercase tracking-wide text-slate-400">Publishing Rules</p>
                                    <ul class="mt-3 space-y-2 text-sm text-slate-300">
                                        <li>Every published skill needs a target proficiency band.</li>
                                        <li>At least one `critical` or `core` skill is required before publish.</li>
                                        <li>Duplicate skills are blocked in the same role mapping.</li>
                                    </ul>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <button type="submit" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-slate-100">
                                        Save Draft
                                    </button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('skills.admin.role-mappings.publish', $workspace['selected_role_id']) }}" class="mt-3">
                                @csrf
                                <button type="submit" class="rounded-xl bg-cyan-500 px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400">
                                    Publish Mapping
                                </button>
                            </form>
                        @else
                            <div class="mt-6 rounded-2xl border border-dashed border-white/10 px-4 py-6 text-sm text-slate-400">
                                Add or select a role to begin mapping skills.
                            </div>
                        @endif
                    </section>

                    <section class="rounded-3xl border border-white/10 bg-slate-900/90 p-6">
                        <h3 class="text-xl font-semibold text-white">Current Published Version</h3>

                        @if ($workspace['published_version'] !== null)
                            <div class="mt-4 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4">
                                <p class="text-sm font-semibold text-white">Version v{{ $workspace['published_version']['version_number'] }}</p>
                                <p class="mt-1 text-sm text-emerald-200">
                                    Published {{ $workspace['published_version']['published_at'] }}
                                    @if ($workspace['published_version']['published_by_name'] !== null)
                                        by {{ $workspace['published_version']['published_by_name'] }}
                                    @endif
                                </p>
                                @if ($workspace['published_version']['summary'] !== null)
                                    <p class="mt-3 text-sm text-slate-200">{{ $workspace['published_version']['summary'] }}</p>
                                @endif
                            </div>

                            <div class="mt-4 space-y-3">
                                @foreach ($workspace['published_version']['skills'] as $skill)
                                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-medium text-white">{{ $skill['skill_name'] }}</p>
                                            <span class="rounded-full bg-white/10 px-2 py-1 text-xs font-medium text-slate-300">
                                                {{ $skill['importance_label'] }}
                                            </span>
                                            <span class="rounded-full bg-white/10 px-2 py-1 text-xs font-medium text-slate-300">
                                                {{ $skill['target_proficiency_label'] }}
                                            </span>
                                        </div>
                                        @if ($skill['rationale_note'] !== null)
                                            <p class="mt-2 text-sm text-slate-400">{{ $skill['rationale_note'] }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-4 rounded-2xl border border-dashed border-white/10 px-4 py-6 text-sm text-slate-400">
                                No published mapping exists for this role yet.
                            </div>
                        @endif
                    </section>
                </div>
            </section>
        </div>
    </div>
</x-skills::app-layout>
