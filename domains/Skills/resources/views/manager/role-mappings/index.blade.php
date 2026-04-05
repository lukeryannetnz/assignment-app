<x-skills::app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-300">Manager View</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-white">Published Role-to-Skill Mappings</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
                    Managers can review stable role expectations before assigning learning or coaching against skill gaps.
                </p>
            </div>

            <div class="rounded-2xl border border-cyan-400/20 bg-cyan-500/10 px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-cyan-300">Visibility</p>
                <p class="mt-1 text-sm font-semibold text-white">Read only</p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('skills::partials.flash-messages')

            <section class="grid gap-6 lg:grid-cols-[0.95fr_1.25fr]">
                <div class="space-y-6">
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                        <h3 class="text-xl font-semibold text-white">Published Mappings</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
                            Open a published mapping to understand the skills and proficiency expectations behind a role.
                        </p>

                        <div class="mt-6 space-y-3">
                            @forelse ($publishedMappings as $mapping)
                                <a
                                    href="{{ route('skills.role-mappings.index', ['role' => $mapping['role_id']]) }}"
                                    class="block rounded-2xl border px-4 py-3 transition {{ $selectedMapping !== null && $selectedMapping['role_id'] === $mapping['role_id'] ? 'border-cyan-400/60 bg-cyan-500/10' : 'border-white/10 bg-white/5 hover:bg-white/10' }}"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-white">{{ $mapping['role_name'] }}</p>
                                            <p class="mt-1 text-sm text-slate-400">{{ $mapping['role_family'] ?? 'Unassigned family' }}</p>
                                        </div>
                                        <span class="rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-medium text-emerald-300">
                                            v{{ $mapping['version_number'] }}
                                        </span>
                                    </div>
                                </a>
                            @empty
                                <div class="rounded-2xl border border-dashed border-white/10 px-4 py-6 text-sm text-slate-400">
                                    No published mappings are available yet.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                        <h3 class="text-xl font-semibold text-white">Proficiency Bands</h3>

                        <div class="mt-4 grid gap-3">
                            @foreach ($proficiencyBands as $band)
                                <div class="rounded-xl bg-slate-900/80 px-4 py-3">
                                    <p class="text-sm font-semibold text-white">{{ $band['label'] }}</p>
                                    <p class="mt-1 text-sm text-slate-400">{{ $band['description'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-slate-900/90 p-6">
                    @if ($selectedMapping !== null)
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-2xl font-semibold text-white">{{ $selectedMapping['role_name'] }}</h3>
                                <p class="mt-2 text-sm text-slate-300">{{ $selectedMapping['role_family'] ?? 'Unassigned family' }}</p>
                                @if ($selectedMapping['role_description'] !== null)
                                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-400">{{ $selectedMapping['role_description'] }}</p>
                                @endif
                            </div>

                            <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-right">
                                <p class="text-xs uppercase tracking-wide text-emerald-300">Published</p>
                                <p class="mt-1 text-sm font-semibold text-white">v{{ $selectedMapping['version_number'] }}</p>
                                <p class="mt-1 text-xs text-emerald-200">
                                    {{ $selectedMapping['published_at'] }}
                                    @if ($selectedMapping['published_by_name'] !== null)
                                        · {{ $selectedMapping['published_by_name'] }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4">
                            @foreach ($selectedMapping['skills'] as $skill)
                                <article class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="text-base font-semibold text-white">{{ $skill['skill_name'] }}</h4>
                                        <span class="rounded-full bg-white/10 px-2.5 py-1 text-xs font-medium text-slate-300">
                                            {{ $skill['importance_label'] }}
                                        </span>
                                        <span class="rounded-full bg-cyan-500/15 px-2.5 py-1 text-xs font-medium text-cyan-300">
                                            {{ $skill['target_proficiency_label'] }}
                                        </span>
                                    </div>

                                    @if ($skill['rationale_note'] !== null)
                                        <p class="mt-3 text-sm leading-6 text-slate-400">{{ $skill['rationale_note'] }}</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-white/10 px-4 py-8 text-sm text-slate-400">
                            Select a published mapping to review role expectations.
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-skills::app-layout>
