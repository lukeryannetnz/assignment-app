<?php

declare(strict_types=1);

namespace App\Http\Controllers\Curriculum;

use App\Models\CurriculumItem;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CurriculumItemController
{
    /**
     * Show the form for creating a new curriculum item.
     */
    public function create(int $sectionId): View
    {
        $section = Section::with('course')->findOrFail($sectionId);

        return view('admin.curriculum_items.create', ['section' => $section]);
    }

    /**
     * Store a newly created quiz curriculum item.
     */
    public function store(Request $request, int $sectionId): RedirectResponse
    {
        $section = Section::findOrFail($sectionId);

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
            'type' => $validated['type'],
            'title' => $validated['title'],
            'order' => $validated['order'],
            'duration_minutes' => count($validated['questions']) * 2,
        ]);

        foreach ($validated['questions'] as $index => $questionData) {
            $curriculumItem->quizQuestions()->create([
                'question' => $questionData['question'],
                'options' => $questionData['options'],
                'correct_answers' => array_map('intval', $questionData['correct_answers']),
                'order' => $index,
            ]);
        }

        return redirect()->route('admin.courses.sections.index', $section->course_id)
            ->with('success', 'Quiz created successfully!');
    }

    /**
     * Show the form for editing a curriculum item.
     */
    public function edit(int $sectionId, int $id): View
    {
        $section = Section::with('course')->findOrFail($sectionId);
        $item = CurriculumItem::with('quizQuestions')
            ->where('section_id', $sectionId)
            ->findOrFail($id);

        return view('admin.curriculum_items.edit', [
            'section' => $section,
            'item' => $item,
        ]);
    }

    /**
     * Update the specified curriculum item.
     */
    public function update(Request $request, int $sectionId, int $id): RedirectResponse
    {
        $section = Section::findOrFail($sectionId);
        $item = CurriculumItem::where('section_id', $sectionId)->findOrFail($id);

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
                'question' => $questionData['question'],
                'options' => $questionData['options'],
                'correct_answers' => array_map('intval', $questionData['correct_answers']),
                'order' => $index,
            ]);
        }

        return redirect()->route('admin.courses.sections.index', $section->course_id)
            ->with('success', 'Quiz updated successfully!');
    }

    /**
     * Remove the specified curriculum item.
     */
    public function destroy(int $sectionId, int $id): RedirectResponse
    {
        $section = Section::findOrFail($sectionId);
        $item = CurriculumItem::where('section_id', $sectionId)->findOrFail($id);
        $item->delete();

        return redirect()->route('admin.courses.sections.index', $section->course_id)
            ->with('success', 'Quiz deleted successfully!');
    }
}
