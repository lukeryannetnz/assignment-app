<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Browse Courses') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials.flash-messages')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @forelse($courses as $course)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                {{ $course->name }}
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                {{ $course->description }}
                            </p>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                    {{ $course->users_count }} {{ Str::plural('student', $course->users_count) }}
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('courses.show', $course->id) }}" class="inline-flex items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                        View Details
                                    </a>
                                    @if(in_array($course->id, $enrolledCourseIds))
                                        <span class="inline-flex items-center px-3 py-2 bg-green-100 dark:bg-green-800 border border-green-200 dark:border-green-700 rounded-md font-semibold text-xs text-green-800 dark:text-green-100 uppercase tracking-widest">
                                            Enrolled
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('courses.enroll', $course->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                                Enroll
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-center text-gray-600 dark:text-gray-400">
                            No courses available yet.
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $courses->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
