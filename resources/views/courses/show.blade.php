<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $course->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @include('partials.flash-messages')

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                            {{ $course->name }}
                        </h3>
                        <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-6">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            {{ $course->users_count }} {{ Str::plural('student', $course->users_count) }} enrolled
                        </div>
                    </div>

                    <div class="mb-8">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                            Course Description
                        </h4>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            {{ $course->description }}
                        </p>
                    </div>

                    <div class="flex gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                        @if($isEnrolled)
                            <span class="inline-flex items-center px-4 py-2 bg-green-100 dark:bg-green-800 border border-green-200 dark:border-green-700 rounded-md font-semibold text-sm text-green-800 dark:text-green-100 uppercase tracking-widest">
                                You are enrolled
                            </span>
                            <form method="POST" action="{{ route('courses.unenroll', $course->id) }}"
                                  onsubmit="return confirm('Are you sure you want to unenroll from this course?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                    Unenroll
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('courses.enroll', $course->id) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                    Enroll in This Course
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('courses.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-400 dark:hover:bg-gray-600">
                            Back to Courses
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
