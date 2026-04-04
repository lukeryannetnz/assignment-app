<x-tenancy::app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ __('Tenant Settings') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Maintain tenant metadata before onboarding users into the org hierarchy.
                </p>
            </div>
            <a
                href="{{ route('tenancy.admin.org-nodes.index') }}"
                class="inline-flex items-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-900"
            >
                Manage Hierarchy
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @include('tenancy::partials.flash-messages')

            <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
                    <form method="POST" action="{{ route('tenancy.admin.tenant.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="ui_form" value="1">

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tenant Name</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name', $tenant['name']) }}"
                                required
                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            >
                        </div>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                <select
                                    id="status"
                                    name="status"
                                    class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                >
                                    <option value="active" @selected(old('status', $tenant['status']) === 'active')>active</option>
                                    <option value="inactive" @selected(old('status', $tenant['status']) === 'inactive')>inactive</option>
                                </select>
                            </div>

                            <div>
                                <label for="plan_tier" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Plan Tier</label>
                                <select
                                    id="plan_tier"
                                    name="plan_tier"
                                    class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                >
                                    @foreach ($planTiers as $planTier)
                                        <option value="{{ $planTier }}" @selected(old('plan_tier', $tenant['plan_tier']) === $planTier)>{{ $planTier }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="hierarchy_depth_limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hierarchy Depth Limit</label>
                            <input
                                id="hierarchy_depth_limit"
                                name="hierarchy_depth_limit"
                                type="number"
                                min="1"
                                max="8"
                                value="{{ old('hierarchy_depth_limit', $tenant['hierarchy_depth_limit']) }}"
                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            >
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" class="inline-flex items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700">
                                Save Tenant Settings
                            </button>
                        </div>
                    </form>
                </div>

                <div class="rounded-2xl bg-gradient-to-br from-sky-950 via-sky-900 to-cyan-800 p-6 text-sky-50 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-200">Current Tenant</h3>
                    <dl class="mt-4 space-y-4 text-sm">
                        <div>
                            <dt class="text-sky-200">Tenant ID</dt>
                            <dd class="mt-1 text-lg font-semibold text-white">{{ $tenant['id'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-sky-200">Status</dt>
                            <dd class="mt-1 capitalize text-white">{{ $tenant['status'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-sky-200">Plan Tier</dt>
                            <dd class="mt-1 text-white">{{ $tenant['plan_tier'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-sky-200">Max Depth</dt>
                            <dd class="mt-1 text-white">{{ $tenant['hierarchy_depth_limit'] }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-tenancy::app-layout>
