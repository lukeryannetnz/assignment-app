<x-curriculum::app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Course Curriculum') }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ $course->name }}
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('course-catalog.admin.courses.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    Back to Courses
                </a>
                <a href="{{ route('curriculum.admin.sections.create', $course->id) }}"
                   class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                    Add Section
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('curriculum::partials.flash-messages')

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @forelse($sections as $section)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 mb-4">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $section->title }}
                                        <span class="text-sm text-gray-500 dark:text-gray-400 font-normal">
                                            (Order: {{ $section->order }})
                                        </span>
                                    </h3>
                                </div>
                                <div class="flex gap-2 ml-4">
                                    <a href="{{ route('curriculum.admin.sections.edit', [$course->id, $section->id]) }}"
                                       class="inline-flex items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                        Edit Section
                                    </a>
                                    <form method="POST"
                                          action="{{ route('curriculum.admin.sections.destroy', [$course->id, $section->id]) }}"
                                          onsubmit="return confirm('Are you sure? This will delete all curriculum items in this section.');"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="flex justify-between items-center mb-3">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Curriculum Items
                                    </h4>
                                    <a href="{{ route('curriculum.admin.items.create', $section->id) }}"
                                       class="inline-flex items-center px-3 py-1.5 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600">
                                        Add Quiz
                                    </a>
                                </div>

                                @if($section->curriculumItems->count() > 0)
                                    <div class="space-y-2">
                                        @foreach($section->curriculumItems as $item)
                                            <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-700 p-3 rounded">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2">
                                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded
                                                            @if($item->type->value === 'quiz') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                                            @elseif($item->type->value === 'video') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300
                                                            @else bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                                            @endif">
                                                            {{ ucfirst($item->type->value) }}
                                                        </span>
                                                        <span class="text-sm text-gray-900 dark:text-gray-100">
                                                            {{ $item->title }}
                                                        </span>
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                                            ({{ $item->duration_minutes }} min)
                                                        </span>
                                                    </div>
                                                    @if($item->type->value === 'quiz' && $item->quizQuestions->count() > 0)
                                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                            {{ $item->quizQuestions->count() }} {{ Str::plural('question', $item->quizQuestions->count()) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex gap-2">
                                                    <a href="{{ route('curriculum.admin.items.edit', [$section->id, $item->id]) }}"
                                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                                                        Edit
                                                    </a>
                                                    <form method="POST"
                                                          action="{{ route('curriculum.admin.items.destroy', [$section->id, $item->id]) }}"
                                                          onsubmit="return confirm('Are you sure you want to delete this item?');"
                                                          class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                                        No curriculum items yet. Add a quiz to get started.
                                    </p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            No sections found. Add a section to organize your course content.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-curriculum::app-layout>
