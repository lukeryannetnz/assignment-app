<x-tenancy::app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ __('Organization Hierarchy') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Create, rename, move, and import org nodes without leaving the admin workflow.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('tenancy.admin.tenant.show') }}"
                    class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50"
                >
                    Tenant Settings
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('tenancy::partials.flash-messages')

            @if ($importResult !== null)
                <section class="rounded-2xl bg-emerald-950 p-6 text-emerald-50 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">Import Completed</h3>
                            <p class="mt-1 text-sm text-emerald-200">
                                {{ $importResult['imported_count'] }} nodes were created from the approved CSV review.
                            </p>
                        </div>
                    </div>
                </section>
            @endif

            @php($nodesById = collect($nodes)->keyBy('id'))
            <section class="space-y-6">
                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Current Tree</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            The list is ordered by depth so operators can verify parent-child relationships quickly.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/40">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    <th class="px-6 py-3">Node</th>
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-6 py-3">Parent</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($nodes as $node)
                                    <tr class="align-top">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3" style="padding-left: {{ $node['depth'] * 1.25 }}rem;">
                                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-sky-100 text-xs font-semibold text-sky-700">
                                                    {{ $node['depth'] }}
                                                </span>
                                                <div>
                                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $node['name'] }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">ID {{ $node['id'] }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $node['node_type'] }}</td>
                                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                            @if ($node['parent_id'] === null)
                                                <div>
                                                    <p class="font-medium text-gray-900 dark:text-gray-100">Root</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">No parent node</p>
                                                </div>
                                            @else
                                                @php($parentNode = $nodesById->get($node['parent_id']))
                                                <div>
                                                    <p class="font-medium text-gray-900 dark:text-gray-100">
                                                        {{ is_array($parentNode) ? $parentNode['name'] : 'Unknown parent' }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        Parent ID {{ $node['parent_id'] }}
                                                    </p>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $node['is_active'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                                {{ $node['is_active'] ? 'active' : 'inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="grid gap-3 lg:grid-cols-3">
                                                <form method="POST" action="{{ route('tenancy.admin.org-nodes.update', $node['id']) }}" class="space-y-2 rounded-xl bg-slate-50 p-3 dark:bg-slate-900/40">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="ui_form" value="1">
                                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Rename</label>
                                                    <input
                                                        type="text"
                                                        name="name"
                                                        value="{{ $node['name'] }}"
                                                        class="block w-full rounded-lg border-gray-300 text-xs shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                                    >
                                                    <button type="submit" class="inline-flex items-center rounded-md bg-sky-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-sky-700">
                                                        Save
                                                    </button>
                                                </form>

                                                @if ($node['node_type'] !== 'company')
                                                    <form method="POST" action="{{ route('tenancy.admin.org-nodes.move', $node['id']) }}" class="space-y-2 rounded-xl bg-slate-50 p-3 dark:bg-slate-900/40">
                                                        @csrf
                                                        <input type="hidden" name="ui_form" value="1">
                                                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Move</label>
                                                        @php($moveParentOptions = $validMoveParentOptions[$node['id']] ?? [])
                                                        @if ($moveParentOptions !== [])
                                                            <select
                                                                name="parent_id"
                                                                class="block w-full rounded-lg border-gray-300 text-xs shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                                            >
                                                                @foreach ($moveParentOptions as $candidateNode)
                                                                    <option
                                                                        value="{{ $candidateNode['id'] }}"
                                                                        data-move-parent-option="1"
                                                                        @selected($candidateNode['id'] === $node['parent_id'])
                                                                    >
                                                                        {{ str_repeat('· ', $candidateNode['depth']) }}{{ $candidateNode['name'] }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <button type="submit" class="inline-flex items-center rounded-md bg-slate-800 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-900">
                                                                Update Parent
                                                            </button>
                                                        @else
                                                            <div class="rounded-lg border border-dashed border-slate-300 px-3 py-2 text-xs text-slate-500">
                                                                No valid parent targets available.
                                                            </div>
                                                        @endif
                                                    </form>
                                                @else
                                                    <div class="rounded-xl border border-dashed border-slate-300 p-3 text-xs text-slate-500">
                                                        Root company node stays at the top of the tree.
                                                    </div>
                                                @endif

                                                <form
                                                    method="POST"
                                                    action="{{ $node['is_active'] ? route('tenancy.admin.org-nodes.deactivate', $node['id']) : route('tenancy.admin.org-nodes.reactivate', $node['id']) }}"
                                                    class="space-y-2 rounded-xl bg-slate-50 p-3 dark:bg-slate-900/40"
                                                >
                                                    @csrf
                                                    <input type="hidden" name="ui_form" value="1">
                                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Lifecycle</label>
                                                    <p class="text-xs text-slate-500">
                                                        {{ $node['is_active'] ? 'Deactivate only after every active descendant node has been turned off.' : 'Reactivate once the parent node is active.' }}
                                                    </p>
                                                    <button
                                                        type="submit"
                                                        class="inline-flex items-center rounded-md px-3 py-2 text-xs font-semibold text-white transition {{ $node['is_active'] ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700' }}"
                                                    >
                                                        {{ $node['is_active'] ? 'Deactivate' : 'Reactivate' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No organization nodes are available yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @php($showCreateNodeForm = old('ui_form') === '1' && old('name') !== null)
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
                    <details class="group" @if($showCreateNodeForm) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 rounded-xl bg-sky-50 px-4 py-3 text-left transition hover:bg-sky-100">
                            <div>
                                <h3 class="text-base font-semibold text-sky-950">Add Node</h3>
                                <p class="mt-1 text-sm text-sky-700">
                                    Open a quick-add form when you need to create a new node beneath the current list.
                                </p>
                                <p class="mt-1 text-xs text-sky-600">
                                    Tenant depth limit: {{ $hierarchyDepthLimit }} total levels including the root company. The root is depth 0, so only parents with depth below {{ $hierarchyDepthLimit - 1 }} can accept another child.
                                </p>
                            </div>
                            <span class="inline-flex items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition group-open:bg-sky-700">
                                New Node
                            </span>
                        </summary>

                        <form method="POST" action="{{ route('tenancy.admin.org-nodes.store') }}" class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1.2fr)_auto] lg:items-end">
                            @csrf
                            <input type="hidden" name="ui_form" value="1">
                            <div>
                                <label for="create_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Node Name</label>
                                <input
                                    id="create_name"
                                    name="name"
                                    type="text"
                                    value="{{ old('name') }}"
                                    required
                                    class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                >
                            </div>

                            <div>
                                <label for="create_node_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Node Type</label>
                                <select
                                    id="create_node_type"
                                    name="node_type"
                                    class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                >
                                    @foreach ($nodeTypes as $nodeType)
                                        <option value="{{ $nodeType }}" @selected(old('node_type') === $nodeType)>{{ $nodeType }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="create_parent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Parent Node</label>
                                <select
                                    id="create_parent_id"
                                    name="parent_id"
                                    class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                >
                                    @foreach ($createParentOptions as $node)
                                        <option
                                            value="{{ $node['id'] }}"
                                            data-create-parent-option="1"
                                            @selected((string) $node['id'] === old('parent_id'))
                                        >
                                            {{ str_repeat('· ', $node['depth']) }}{{ $node['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700">
                                Create Node
                            </button>
                        </form>
                    </details>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">CSV Import Review</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Dry-run the file first, then commit the reviewed result once the hierarchy is validated.
                            </p>
                        </div>
                        <a
                            href="{{ route('tenancy.admin.org-nodes.imports.sample') }}"
                            class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50"
                        >
                            Download Sample CSV
                        </a>
                    </div>

                    <form method="POST" action="{{ route('tenancy.admin.org-nodes.imports.dry-run') }}" enctype="multipart/form-data" class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                        @csrf
                        <input type="hidden" name="ui_form" value="1">
                        <div>
                            <label for="csv_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">CSV File</label>
                            <input
                                id="csv_file"
                                name="csv_file"
                                type="file"
                                accept=".csv,text/csv"
                                class="mt-1 block w-full rounded-lg border border-dashed border-gray-300 bg-slate-50 px-3 py-3 text-sm text-gray-600"
                            >
                        </div>

                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-900">
                            Run Dry-Run Validation
                        </button>
                    </form>

                    @if ($importPreview !== null)
                        <div class="mt-6 space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">Dry-Run Results</h4>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $importPreview['can_commit'] ? 'The file is ready to commit.' : 'Blocking issues must be resolved before commit.' }}
                                    </p>
                                </div>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $importPreview['can_commit'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $importPreview['can_commit'] ? 'Ready' : 'Blocked' }}
                                </span>
                            </div>

                            @if ($importPreview['errors'] !== [])
                                <div class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">
                                    <p class="font-semibold">Blocking errors</p>
                                    <ul class="mt-2 space-y-2">
                                        @foreach ($importPreview['errors'] as $error)
                                            <li>Row {{ $error['row_number'] }} [{{ $error['field'] }}]: {{ $error['message'] }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="max-h-72 overflow-y-auto rounded-xl bg-white ring-1 ring-slate-200">
                                <table class="min-w-full divide-y divide-slate-200 text-xs">
                                    <thead class="bg-slate-100 text-left font-semibold uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="px-3 py-2">Row</th>
                                            <th class="px-3 py-2">Key</th>
                                            <th class="px-3 py-2">Parent</th>
                                            <th class="px-3 py-2">Type</th>
                                            <th class="px-3 py-2">Name</th>
                                            <th class="px-3 py-2">Depth</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($importPreview['rows'] as $row)
                                            <tr>
                                                <td class="px-3 py-2">{{ $row['row_number'] }}</td>
                                                <td class="px-3 py-2">{{ $row['row_key'] }}</td>
                                                <td class="px-3 py-2">{{ $row['parent_row_key'] ?? 'root' }}</td>
                                                <td class="px-3 py-2">{{ $row['node_type'] ?? 'invalid' }}</td>
                                                <td class="px-3 py-2">{{ $row['name'] }}</td>
                                                <td class="px-3 py-2">{{ $row['resolved_depth'] ?? 'n/a' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <form method="POST" action="{{ route('tenancy.admin.org-nodes.imports.commit') }}">
                                @csrf
                                <input type="hidden" name="ui_form" value="1">
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white transition {{ $importPreview['can_commit'] ? 'bg-emerald-600 hover:bg-emerald-700' : 'cursor-not-allowed bg-slate-300' }}"
                                    @disabled(! $importPreview['can_commit'])
                                >
                                    Commit Reviewed Import
                                </button>
                            </form>
                        </div>
                    @endif
                </section>
            </section>
        </div>
    </div>
</x-tenancy::app-layout>
