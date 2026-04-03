<?php

declare(strict_types=1);

namespace App\Domains\Curriculum\Http\Controllers;

use App\Domains\Curriculum\Models\CurriculumItem;
use App\Domains\Curriculum\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Nette\ArgumentOutOfRangeException;

class CurriculumItemController
{
    /**
     * Show the form for creating a new curriculum item.
     */
    public function create(int $sectionId): View
    {
        $user = request()->user();
        if ($user == null || $user->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }
        $section = Section::where('tenant_id', $user->tenant_id)->with('course')->findOrFail($sectionId);

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
        $section = Section::where('tenant_id', $user->tenant_id)->findOrFail($sectionId);

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

        $curriculumItem = $section->curriculumItems()->create([
            'tenant_id' => $user->tenant_id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'order' => $validated['order'],
            'duration_minutes' => count($validated['questions']) * 2,
        ]);

        foreach ($validated['questions'] as $index => $questionData) {
            $curriculumItem->quizQuestions()->create([
                'tenant_id' => $user->tenant_id,
                'question' => $questionData['question'],
                'options' => $questionData['options'],
                'correct_answers' => array_map('intval', $questionData['correct_answers']),
                'order' => $index,
            ]);
        }

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
        $section = Section::where('tenant_id', $user->tenant_id)->with('course')->findOrFail($sectionId);
        $item = CurriculumItem::with('quizQuestions')
            ->where('section_id', $sectionId)
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

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

        $section = Section::where('tenant_id', $user->tenant_id)->findOrFail($sectionId);
        $item = CurriculumItem::where('section_id', $sectionId)
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

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

        $item->update([
            'title' => $validated['title'],
            'order' => $validated['order'],
            'duration_minutes' => count($validated['questions']) * 2,
        ]);

        $item->quizQuestions()->delete();

        foreach ($validated['questions'] as $index => $questionData) {
            $item->quizQuestions()->create([
                'tenant_id' => $user->tenant_id,
                'question' => $questionData['question'],
                'options' => $questionData['options'],
                'correct_answers' => array_map('intval', $questionData['correct_answers']),
                'order' => $index,
            ]);
        }

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
        $section = Section::where('tenant_id', $user->tenant_id)->findOrFail($sectionId);
        $item = CurriculumItem::where('section_id', $sectionId)
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);
        $item->delete();

        return redirect()->route('curriculum.admin.sections.index', $section->course_id)
            ->with('success', 'Quiz deleted successfully!');
    }
}
