<nav class="border-b border-white/10 bg-slate-950/95">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-3">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-500/15 text-sm font-semibold text-cyan-300 ring-1 ring-inset ring-cyan-400/30">
                        S
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-white">Skills Intelligence</p>
                        <p class="text-xs text-slate-400">Role-to-skill mappings</p>
                    </div>
                </a>

                <div class="hidden items-center gap-2 md:flex">
                    <a
                        href="{{ route('skills.role-mappings.index') }}"
                        class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('skills.role-mappings.index') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                    >
                        Published Mappings
                    </a>

                    @if (Auth::user()->isAdmin())
                        <a
                            href="{{ route('skills.admin.role-mappings.index') }}"
                            class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('skills.admin.role-mappings.index') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                        >
                            Admin Workspace
                        </a>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-medium text-white">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-slate-400">{{ Auth::user()->email }}</p>
                </div>

                <form method="POST" action="{{ route('identity-access.auth.logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-lg bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/10 hover:text-white"
                    >
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
