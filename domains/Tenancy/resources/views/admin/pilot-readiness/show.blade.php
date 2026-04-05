<x-tenancy::app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ __('Pilot Readiness') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Use one onboarding playbook for provisioning, hierarchy setup, KPI validation, and go/no-go review.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('tenancy.admin.tenant.show') }}"
                    class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50"
                >
                    Tenant Settings
                </a>
                <a
                    href="{{ route('tenancy.admin.org-nodes.index') }}"
                    class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-950"
                >
                    Manage Hierarchy
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('tenancy::partials.flash-messages')

            <section class="grid gap-4 lg:grid-cols-4">
                <div class="rounded-2xl bg-slate-950 p-5 text-slate-50 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Tenant</p>
                    <p class="mt-3 text-2xl font-semibold">{{ $summary['tenant']['name'] }}</p>
                    <p class="mt-2 text-sm text-slate-300">Root company: {{ $summary['tenant']['root_company_name'] }}</p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Setup Duration</p>
                    <p class="mt-3 text-2xl font-semibold text-slate-900">
                        {{ $summary['metrics']['onboarding_duration_hours'] !== null ? number_format($summary['metrics']['onboarding_duration_hours'], 2) . 'h' : 'Pending' }}
                    </p>
                    <p class="mt-2 text-sm text-slate-500">
                        Measured from `tenant_created` to the first active team node.
                    </p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Integrity Error Rate</p>
                    <p class="mt-3 text-2xl font-semibold text-slate-900">
                        {{ number_format($summary['metrics']['hierarchy_integrity_error_rate'], 2) }}%
                    </p>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ $summary['metrics']['hierarchy_integrity_error_count'] }} integrity errors across {{ $summary['metrics']['hierarchy_write_count'] + $summary['metrics']['hierarchy_integrity_error_count'] }} attempted writes.
                    </p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Assignment-Ready Teams</p>
                    <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $summary['metrics']['active_team_count'] }}</p>
                    <p class="mt-2 text-sm text-slate-500">
                        Active teams are the readiness marker for pilot onboarding completion.
                    </p>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Pilot Onboarding Checklist</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                This is the repeatable workflow for internal alpha and design-partner onboarding.
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        @foreach ($summary['onboarding_checklist'] as $item)
                            @php($statusClasses = match ($item['status']) {
                                'complete' => 'bg-emerald-100 text-emerald-700',
                                'go' => 'bg-emerald-100 text-emerald-700',
                                'hold' => 'bg-rose-100 text-rose-700',
                                default => 'bg-amber-100 text-amber-700',
                            })
                            <article class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h4 class="text-sm font-semibold text-slate-900">{{ $item['title'] }}</h4>
                                        <p class="mt-1 text-sm text-slate-500">{{ $item['detail'] }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                                            {{ str_replace('_', ' ', $item['status']) }}
                                        </span>
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                            {{ $item['owner'] }}
                                        </span>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                        <h3 class="text-base font-semibold text-slate-900">Go / No-Go Checklist</h3>
                        <div class="mt-5 space-y-3">
                            @foreach ($summary['go_no_go_checklist'] as $item)
                                @php($statusClasses = $item['status'] === 'go' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700')
                                <article class="rounded-2xl border border-slate-200 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h4 class="text-sm font-semibold text-slate-900">{{ $item['title'] }}</h4>
                                            <p class="mt-1 text-sm text-slate-500">{{ $item['detail'] }}</p>
                                        </div>
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                                            {{ $item['status'] }}
                                        </span>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-2xl bg-gradient-to-br from-sky-950 via-cyan-900 to-teal-800 p-6 text-white shadow-sm">
                        <h3 class="text-base font-semibold">Shared Onboarding Playbook</h3>
                        <div class="mt-5 space-y-4">
                            @foreach ($summary['playbook'] as $step)
                                <article class="rounded-2xl bg-white/10 p-4 backdrop-blur-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <h4 class="text-sm font-semibold">{{ $step['phase'] }}</h4>
                                        <span class="inline-flex rounded-full bg-white/15 px-2.5 py-1 text-xs font-semibold text-cyan-100">
                                            {{ $step['owner'] }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm text-cyan-50/90">{{ $step['guidance'] }}</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>

            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Hierarchy Starter Templates</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Seed the pilot with common org structures, then validate the file through the tenancy CSV dry-run.
                        </p>
                    </div>
                    <a
                        href="{{ route('tenancy.admin.org-nodes.index') }}"
                        class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-950"
                    >
                        Open Import Workflow
                    </a>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-3">
                    @foreach ($summary['templates'] as $template)
                        <article class="rounded-2xl border border-slate-200 p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">{{ $template['name'] }}</h4>
                                    <p class="mt-2 text-sm text-slate-500">{{ $template['description'] }}</p>
                                </div>
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                    {{ $template['row_count'] }} rows
                                </span>
                            </div>
                            <a
                                href="{{ $template['download_url'] }}"
                                class="mt-4 inline-flex items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700"
                            >
                                Download CSV
                            </a>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-tenancy::app-layout>
