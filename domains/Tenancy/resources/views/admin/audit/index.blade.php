<x-tenancy::app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ __('Audit & Compliance') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Review recent tenancy lifecycle activity and the phase 1 compliance guardrails for this tenant.
                </p>
            </div>
            <a
                href="{{ route('tenancy.admin.tenant.show') }}"
                class="inline-flex items-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-900"
            >
                Tenant Settings
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Recent Audit Rows</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            Showing the most recent records retained inside the 12-month pilot compliance window.
                        </p>
                    </div>

                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($logs as $log)
                            <article class="px-6 py-5">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $log['action'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $log['auditable_type'] }} #{{ $log['auditable_id'] }}
                                            @if ($log['actor_user_id'] !== null)
                                                · actor #{{ $log['actor_user_id'] }}
                                            @endif
                                        </p>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $log['created_at'] }}</p>
                                </div>
                                <pre class="mt-4 overflow-x-auto rounded-xl bg-slate-950/95 p-4 text-xs text-slate-100">{{ json_encode($log['metadata'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </article>
                        @empty
                            <div class="px-6 py-8 text-sm text-gray-600 dark:text-gray-300">
                                No audit rows were found inside the current retention window.
                            </div>
                        @endforelse
                    </div>
                </div>

                <aside class="space-y-6">
                    <section class="rounded-2xl bg-gradient-to-br from-sky-950 via-sky-900 to-cyan-800 p-6 text-sky-50 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-200">Retention</h3>
                        <p class="mt-3 text-sm leading-6 text-sky-100">
                            Minimum retention is {{ $compliance['minimum_retention_months'] }} months. This review view only reads rows from
                            {{ $compliance['retention_window_start'] }} onward.
                        </p>
                        <p class="mt-3 text-sm leading-6 text-sky-100">
                            Access scope: {{ $compliance['access_scope'] }}
                        </p>
                    </section>

                    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Security Review Checklist</h3>
                        <ul class="mt-4 space-y-3 text-sm text-gray-700 dark:text-gray-200">
                            @foreach ($compliance['checklist'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-tenancy::app-layout>
