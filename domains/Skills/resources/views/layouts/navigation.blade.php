<nav class="border-b border-gray-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-3">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-sky-100 text-sm font-semibold text-sky-700 ring-1 ring-inset ring-sky-200">
                        S
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Skills Intelligence</p>
                        <p class="text-xs text-slate-500">Role-to-skill mappings</p>
                    </div>
                </a>

                <div class="hidden items-center gap-2 md:flex">
                    <a
                        href="{{ route('skills.role-mappings.index') }}"
                        class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('skills.role-mappings.index') ? 'bg-sky-100 text-sky-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                    >
                        Published Mappings
                    </a>

                    @if (Auth::user()->isAdmin())
                        <a
                            href="{{ route('skills.admin.role-mappings.index') }}"
                            class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('skills.admin.role-mappings.index') ? 'bg-sky-100 text-sky-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                        >
                            Admin Workspace
                        </a>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-medium text-slate-900">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-slate-500">{{ Auth::user()->email }}</p>
                </div>

                <form method="POST" action="{{ route('identity-access.auth.logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200 hover:text-slate-900"
                    >
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
