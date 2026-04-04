<x-tenancy::app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ __('Provision Tenant') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Create a tenant shell and root company node for pilot onboarding.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[minmax(0,2fr)_minmax(20rem,1fr)] lg:px-8">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
                @include('tenancy::partials.flash-messages')

                <form method="POST" action="{{ route('tenancy.admin.tenants.store') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="ui_form" value="1">

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tenant Name</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            required
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                        >
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label for="plan_tier" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Plan Tier</label>
                            <select
                                id="plan_tier"
                                name="plan_tier"
                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            >
                                <option value="">enterprise_pilot</option>
                                @foreach ($planTiers as $planTier)
                                    <option value="{{ $planTier }}" @selected(old('plan_tier') === $planTier)>{{ $planTier }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="hierarchy_depth_limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hierarchy Depth Limit</label>
                            <input
                                id="hierarchy_depth_limit"
                                name="hierarchy_depth_limit"
                                type="number"
                                min="1"
                                max="8"
                                value="{{ old('hierarchy_depth_limit', 4) }}"
                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="root_org_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Root Organization Name</label>
                        <input
                            id="root_org_name"
                            name="root_org_name"
                            type="text"
                            value="{{ old('root_org_name') }}"
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                        >
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Leave blank to mirror the tenant name.
                        </p>
                    </div>

                    <div class="flex items-center justify-end">
                        <button type="submit" class="inline-flex items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700">
                            Create Tenant Shell
                        </button>
                    </div>
                </form>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl bg-slate-900 p-6 text-slate-100 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-300">What This Creates</h3>
                    <ul class="mt-4 space-y-3 text-sm text-slate-200">
                        <li>A tenant record with the selected plan tier and hierarchy limit.</li>
                        <li>A root `company` org node ready for hierarchy expansion.</li>
                        <li>Audit rows for `tenant_created` and the root `org_node_created` event.</li>
                    </ul>
                </div>

                @if (session('provisioning_result'))
                    @php($result = session('provisioning_result'))
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Latest Provisioned Tenant</h3>
                        <dl class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                            <div class="flex items-center justify-between gap-4">
                                <dt>Name</dt>
                                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $result['tenant']['name'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt>Plan</dt>
                                <dd>{{ $result['tenant']['plan_tier'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt>Depth Limit</dt>
                                <dd>{{ $result['tenant']['hierarchy_depth_limit'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt>Root Node</dt>
                                <dd>{{ $result['root_org_node']['name'] }}</dd>
                            </div>
                        </dl>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-tenancy::app-layout>
