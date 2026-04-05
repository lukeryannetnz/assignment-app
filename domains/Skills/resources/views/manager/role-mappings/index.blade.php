<x-skills::app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-700">Role Maps</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Published Role Expectations</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    This page is read-only. Choose a role on the left to review the published skills and target proficiency bands managers should work from.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-slate-500">Visibility</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">Read only</p>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('skills::partials.flash-messages')

            <section class="rounded-3xl border border-sky-200 bg-sky-50 p-6">
                <h3 class="text-base font-semibold text-sky-950">How to use this page</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Select a published role map from the list, then review the role summary, skills, importance, and target proficiency in the detail panel.
                </p>
            </section>

            <section class="grid gap-6 lg:grid-cols-[0.95fr_1.25fr]">
                <div class="space-y-6">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-xl font-semibold text-slate-900">Published Role Maps</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Select a role to load its current published mapping.
                        </p>

                        <div class="mt-6 space-y-3">
                            @forelse ($publishedMappings as $mapping)
                                <a
                                    href="{{ route('skills.role-mappings.index', ['role' => $mapping['role_id']]) }}"
                                    class="block rounded-2xl border px-4 py-3 transition {{ $selectedMapping !== null && $selectedMapping['role_id'] === $mapping['role_id'] ? 'border-sky-300 bg-sky-50' : 'border-slate-200 bg-white hover:bg-slate-50' }}"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $mapping['role_name'] }}</p>
                                            <p class="mt-1 text-sm text-slate-600">{{ $mapping['role_family'] ?? 'Unassigned family' }}</p>
                                        </div>
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-800">
                                            v{{ $mapping['version_number'] }}
                                        </span>
                                    </div>
                                </a>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500">
                                    No published mappings are available yet.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-xl font-semibold text-slate-900">Proficiency Bands</h3>

                        <div class="mt-4 grid gap-3">
                            @foreach ($proficiencyBands as $band)
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <p class="text-sm font-semibold text-slate-900">{{ $band['label'] }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $band['description'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    @if ($selectedMapping !== null)
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h3 class="text-2xl font-semibold text-slate-900">{{ $selectedMapping['role_name'] }}</h3>
                                <p class="mt-2 text-sm text-slate-600">{{ $selectedMapping['role_family'] ?? 'Unassigned family' }}</p>
                                @if ($selectedMapping['role_description'] !== null)
                                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ $selectedMapping['role_description'] }}</p>
                                @endif
                            </div>

                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-right">
                                <p class="text-xs uppercase tracking-wide text-emerald-700">Published</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">v{{ $selectedMapping['version_number'] }}</p>
                                <p class="mt-1 text-xs text-slate-600">
                                    {{ $selectedMapping['published_at'] }}
                                    @if ($selectedMapping['published_by_name'] !== null)
                                        · {{ $selectedMapping['published_by_name'] }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4">
                            @foreach ($selectedMapping['skills'] as $skill)
                                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="text-base font-semibold text-slate-900">{{ $skill['skill_name'] }}</h4>
                                        <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700">
                                            {{ $skill['importance_label'] }}
                                        </span>
                                        <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-800">
                                            {{ $skill['target_proficiency_label'] }}
                                        </span>
                                    </div>

                                    @if ($skill['rationale_note'] !== null)
                                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $skill['rationale_note'] }}</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-sm text-slate-500">
                            Select a published mapping to review role expectations.
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-skills::app-layout>
