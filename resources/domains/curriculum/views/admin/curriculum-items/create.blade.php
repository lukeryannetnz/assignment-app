<x-curriculum::app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Add Quiz') }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ $section->course->name }} / {{ $section->title }}
                </p>
            </div>
            <a href="{{ route('curriculum.admin.sections.index', $section->course_id) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Cancel
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('curriculum.admin.items.store', $section->id) }}" id="quizForm">
                        @csrf
                        <input type="hidden" name="type" value="quiz">

                        <div class="mb-4">
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Quiz Title
                            </label>
                            <input type="text"
                                   id="title"
                                   name="title"
                                   value="{{ old('title') }}"
                                   required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-sm">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Order
                            </label>
                            <input type="number"
                                   id="order"
                                   name="order"
                                   value="{{ old('order', 0) }}"
                                   min="0"
                                   required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-sm">
                            @error('order')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    Questions
                                    <span class="text-sm text-gray-500 font-normal">(Duration: 2 min per question)</span>
                                </h3>
                                <button type="button" onclick="addQuestion()"
                                        class="inline-flex items-center px-3 py-2 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600">
                                    Add Question
                                </button>
                            </div>

                            <div id="questions-container"></div>

                            @error('questions')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-3">
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                                Create Quiz
                            </button>
                            <a href="{{ route('curriculum.admin.sections.index', $section->course_id) }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let questionIndex = 0;

        function addQuestion() {
            const container = document.getElementById('questions-container');
            const questionDiv = document.createElement('div');
            questionDiv.className = 'border border-gray-300 dark:border-gray-600 rounded-lg p-4 mb-4';
            questionDiv.id = `question-${questionIndex}`;

            questionDiv.innerHTML = `
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100">Question ${questionIndex + 1}</h4>
                    <button type="button" onclick="removeQuestion(${questionIndex})"
                            class="text-red-600 hover:text-red-800 dark:text-red-400 text-sm">
                        Remove
                    </button>
                </div>

                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Question Text
                    </label>
                    <textarea name="questions[${questionIndex}][question]"
                              required
                              rows="2"
                              class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-sm"></textarea>
                </div>

                <div class="mb-3">
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Options
                        </label>
                        <button type="button" onclick="addOption(${questionIndex})"
                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-sm">
                            Add Option
                        </button>
                    </div>
                    <div id="options-${questionIndex}"></div>
                </div>
            `;

            container.appendChild(questionDiv);

            addOption(questionIndex);
            addOption(questionIndex);

            questionIndex++;
        }

        function removeQuestion(index) {
            const questionDiv = document.getElementById(`question-${index}`);
            if (questionDiv) {
                questionDiv.remove();
            }
        }

        let optionIndexes = {};

        function addOption(questionIdx) {
            if (!optionIndexes[questionIdx]) {
                optionIndexes[questionIdx] = 0;
            }

            const optionIndex = optionIndexes[questionIdx];
            const container = document.getElementById(`options-${questionIdx}`);
            const optionDiv = document.createElement('div');
            optionDiv.className = 'flex gap-2 mb-2';
            optionDiv.id = `option-${questionIdx}-${optionIndex}`;

            optionDiv.innerHTML = `
                <input type="text"
                       name="questions[${questionIdx}][options][${optionIndex}]"
                       required
                       placeholder="Option ${optionIndex + 1}"
                       class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-sm">
                <label class="flex items-center">
                    <input type="checkbox"
                           name="questions[${questionIdx}][correct_answers][]"
                           value="${optionIndex}"
                           class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Correct</span>
                </label>
                <button type="button" onclick="removeOption(${questionIdx}, ${optionIndex})"
                        class="text-red-600 hover:text-red-800 dark:text-red-400 text-sm px-2">
                    Remove
                </button>
            `;

            container.appendChild(optionDiv);
            optionIndexes[questionIdx]++;
        }

        function removeOption(questionIdx, optionIdx) {
            const optionDiv = document.getElementById(`option-${questionIdx}-${optionIdx}`);
            if (optionDiv) {
                optionDiv.remove();
            }
        }

        addQuestion();
    </script>
    @endpush
</x-curriculum::app-layout>
