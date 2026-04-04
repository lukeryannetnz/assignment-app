<?php

declare(strict_types=1);

namespace App\Domains\Curriculum\Http\Controllers;

use App\Domains\Curriculum\Data\QuizItemInputData;
use App\Domains\Curriculum\Data\QuizQuestionInputData;
use App\Domains\Curriculum\Services\CurriculumService;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Nette\ArgumentOutOfRangeException;

class CurriculumItemController
{
    public function __construct(private readonly CurriculumService $curriculumService)
    {
    }

    /**
     * Show the form for creating a new curriculum item.
     */
    public function create(int $sectionId): View
    {
        $user = request()->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }
        $section = $this->curriculumService->findSectionForItem($user->tenant_id, $sectionId);

        return view('curriculum::admin.curriculum-items.create', ['section' => $section]);
    }

    /**
     * Store a newly created quiz curriculum item.
     */
    public function store(Request $request, int $sectionId): RedirectResponse
    {
        $user = $request->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }
        $section = $this->curriculumService->findSectionForItem($user->tenant_id, $sectionId);

        $validated = $request->validate([
            'type' => 'required|in:quiz',
            'title' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.options.*' => 'required|string',
            'questions.*.correct_answers' => 'required|array|min:1',
            'questions.*.correct_answers.*' => 'required|integer|min:0',
        ]);

        $this->curriculumService->createQuizItem(
            $user->tenant_id,
            $sectionId,
            $this->quizItemInputFromValidated($validated),
        );

        return redirect()->route('curriculum.admin.sections.index', $section->course_id)
            ->with('success', 'Quiz created successfully!');
    }

    /**
     * Show the form for editing a curriculum item.
     */
    public function edit(int $sectionId, int $id): View
    {
        $user = request()->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }
        $section = $this->curriculumService->findSectionForItem($user->tenant_id, $sectionId);
        $item = $this->curriculumService->findItem($user->tenant_id, $sectionId, $id);

        return view('curriculum::admin.curriculum-items.edit', [
            'section' => $section,
            'item' => $item,
        ]);
    }

    /**
     * Update the specified curriculum item.
     */
    public function update(Request $request, int $sectionId, int $id): RedirectResponse
    {
        $user = $request->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }

        $section = $this->curriculumService->findSectionForItem($user->tenant_id, $sectionId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.options.*' => 'required|string',
            'questions.*.correct_answers' => 'required|array|min:1',
            'questions.*.correct_answers.*' => 'required|integer|min:0',
        ]);

        $this->curriculumService->updateQuizItem(
            $user->tenant_id,
            $sectionId,
            $id,
            $this->quizItemInputFromValidated($validated),
        );

        return redirect()->route('curriculum.admin.sections.index', $section->course_id)
            ->with('success', 'Quiz updated successfully!');
    }

    /**
     * Remove the specified curriculum item.
     */
    public function destroy(int $sectionId, int $id): RedirectResponse
    {
        $user = request()->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }
        $section = $this->curriculumService->findSectionForItem($user->tenant_id, $sectionId);
        $this->curriculumService->deleteQuizItem($user->tenant_id, $sectionId, $id);

        return redirect()->route('curriculum.admin.sections.index', $section->course_id)
            ->with('success', 'Quiz deleted successfully!');
    }

    /**
     * @param array{
     *   title: string,
     *   order: int,
     *   questions: array<int, array{
     *     question: string,
     *     options: array<int, string>,
     *     correct_answers: array<int, int>
     *   }>
     * } $validated
     */
    private function quizItemInputFromValidated(array $validated): QuizItemInputData
    {
        /** @var Collection<int, QuizQuestionInputData> $questions */
        $questions = collect($validated['questions'])->values()->map(
            static fn (array $question): QuizQuestionInputData => new QuizQuestionInputData(
                question: $question['question'],
                options: array_values($question['options']),
                correctAnswers: array_map('intval', array_values($question['correct_answers'])),
            ),
        );

        return new QuizItemInputData(
            title: $validated['title'],
            order: (int) $validated['order'],
            questions: $questions,
        );
    }
}
