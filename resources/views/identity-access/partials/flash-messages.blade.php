@if (session('success'))
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative dark:bg-green-800 dark:border-green-600 dark:text-green-100" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative dark:bg-red-800 dark:border-red-600 dark:text-red-100" role="alert">
        <span class="block sm:inline">{{ session('error') }}</span>
    </div>
@endif

@if (session('info'))
    <div class="mb-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative dark:bg-blue-800 dark:border-blue-600 dark:text-blue-100" role="alert">
        <span class="block sm:inline">{{ session('info') }}</span>
    </div>
@endif
