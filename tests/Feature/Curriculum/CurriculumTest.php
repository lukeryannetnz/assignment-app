<?php

declare(strict_types=1);

namespace Tests\Feature\Curriculum;

use App\Models\CourseCatalog\Course;
use App\Models\Curriculum\CurriculumItem;
use App\Models\QuizQuestion;
use App\Models\Curriculum\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @phpstan-ignore property.uninitialized
     */
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function testSectionIndexPageRequiresAuthentication(): void
    {
        $course = Course::factory()->create();

        $response = $this->get(route('admin.courses.sections.index', $course->id));

        $response->assertRedirect('/login');
    }

    public function testSectionIndexPageRequiresAdminRole(): void
    {
        $student = User::factory()->create(['is_admin' => false]);
        $course = Course::factory()->create();

        $response = $this->actingAs($student)->get(route('admin.courses.sections.index', $course->id));

        $response->assertForbidden();
    }

    public function testSectionIndexDisplaysSections(): void
    {
        $course = Course::factory()->create();
        Section::factory()->create(['course_id' => $course->id, 'title' => 'Introduction']);
        Section::factory()->create(['course_id' => $course->id, 'title' => 'Advanced Topics']);

        $response = $this->actingAs($this->admin)->get(route('admin.courses.sections.index', $course->id));

        $response->assertOk();
        $response->assertSee('Introduction');
        $response->assertSee('Advanced Topics');
    }

    public function testCanCreateSection(): void
    {
        $course = Course::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('admin.courses.sections.store', $course->id), [
            'title' => 'Getting Started',
            'order' => 1,
        ]);

        $response->assertRedirect(route('admin.courses.sections.index', $course->id));
        $this->assertDatabaseHas('sections', [
            'course_id' => $course->id,
            'title' => 'Getting Started',
            'order' => 1,
        ]);
    }

    public function testCannotCreateSectionWithoutTitle(): void
    {
        $course = Course::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('admin.courses.sections.store', $course->id), [
            'order' => 1,
        ]);

        $response->assertSessionHasErrors('title');
    }

    public function testCannotCreateSectionWithoutOrder(): void
    {
        $course = Course::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('admin.courses.sections.store', $course->id), [
            'title' => 'Getting Started',
        ]);

        $response->assertSessionHasErrors('order');
    }

    public function testCanUpdateSection(): void
    {
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id, 'title' => 'Old Title']);

        $response = $this->actingAs($this->admin)->put(
            route('admin.courses.sections.update', [$course->id, $section->id]),
            [
                'title' => 'New Title',
                'order' => 2,
            ]
        );

        $response->assertRedirect(route('admin.courses.sections.index', $course->id));
        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'title' => 'New Title',
            'order' => 2,
        ]);
    }

    public function testCanDeleteSection(): void
    {
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.courses.sections.destroy', [$course->id, $section->id]));

        $response->assertRedirect(route('admin.courses.sections.index', $course->id));
        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
    }

    public function testCanCreateQuizWithQuestions(): void
    {
        $section = Section::factory()->create();

        $quizData = [
            'type' => 'quiz',
            'title' => 'PHP Basics Quiz',
            'order' => 1,
            'questions' => [
                [
                    'question' => 'What does PHP stand for?',
                    'options' => [
                        'PHP Hypertext Preprocessor',
                        'Personal Home Page',
                        'Private Home Page',
                        'Public Hypertext Protocol',
                    ],
                    'correct_answers' => [0],
                ],
                [
                    'question' => 'Which operator is used for string concatenation?',
                    'options' => ['+', '.', '&', ','],
                    'correct_answers' => [1],
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.sections.items.store', $section->id), $quizData);

        $response->assertRedirect(route('admin.courses.sections.index', $section->course_id));

        $this->assertDatabaseHas('curriculum_items', [
            'section_id' => $section->id,
            'type' => 'quiz',
            'title' => 'PHP Basics Quiz',
            'duration_minutes' => 4,
        ]);

        $item = CurriculumItem::where('section_id', $section->id)->first();
        $this->assertNotNull($item);
        $this->assertCount(2, $item->quizQuestions);
    }

    public function testQuizDurationCalculatedBasedOnQuestionCount(): void
    {
        $section = Section::factory()->create();

        $quizData = [
            'type' => 'quiz',
            'title' => 'Test Quiz',
            'order' => 1,
            'questions' => [
                [
                    'question' => 'Question 1?',
                    'options' => ['A', 'B'],
                    'correct_answers' => [0],
                ],
                [
                    'question' => 'Question 2?',
                    'options' => ['A', 'B'],
                    'correct_answers' => [0],
                ],
                [
                    'question' => 'Question 3?',
                    'options' => ['A', 'B'],
                    'correct_answers' => [0],
                ],
            ],
        ];

        $this->actingAs($this->admin)->post(route('admin.sections.items.store', $section->id), $quizData);

        $this->assertDatabaseHas('curriculum_items', [
            'section_id' => $section->id,
            'duration_minutes' => 6,
        ]);
    }

    public function testCanUpdateQuiz(): void
    {
        $section = Section::factory()->create();
        $item = CurriculumItem::factory()->create([
            'section_id' => $section->id,
            'type' => 'quiz',
            'title' => 'Old Quiz',
        ]);

        QuizQuestion::factory()->create([
            'curriculum_item_id' => $item->id,
            'question' => 'Old question?',
            'options' => ['A', 'B'],
            'correct_answers' => [0],
        ]);

        $updateData = [
            'title' => 'Updated Quiz',
            'order' => 2,
            'questions' => [
                [
                    'question' => 'New question 1?',
                    'options' => ['Option 1', 'Option 2', 'Option 3'],
                    'correct_answers' => [0, 2],
                ],
                [
                    'question' => 'New question 2?',
                    'options' => ['Yes', 'No'],
                    'correct_answers' => [1],
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.sections.items.update', [$section->id, $item->id]), $updateData);

        $response->assertRedirect(route('admin.courses.sections.index', $section->course_id));

        $item->refresh();
        $this->assertEquals('Updated Quiz', $item->title);
        $this->assertEquals(4, $item->duration_minutes);
        $this->assertCount(2, $item->quizQuestions);
    }

    public function testCanDeleteQuiz(): void
    {
        $section = Section::factory()->create();
        $item = CurriculumItem::factory()->create(['section_id' => $section->id, 'type' => 'quiz']);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.sections.items.destroy', [$section->id, $item->id]));

        $response->assertRedirect(route('admin.courses.sections.index', $section->course_id));
        $this->assertDatabaseMissing('curriculum_items', ['id' => $item->id]);
    }

    public function testQuizRequiresAtLeastOneQuestion(): void
    {
        $section = Section::factory()->create();

        $quizData = [
            'type' => 'quiz',
            'title' => 'Empty Quiz',
            'order' => 1,
            'questions' => [],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.sections.items.store', $section->id), $quizData);

        $response->assertSessionHasErrors('questions');
    }

    public function testQuizQuestionRequiresAtLeastTwoOptions(): void
    {
        $section = Section::factory()->create();

        $quizData = [
            'type' => 'quiz',
            'title' => 'Test Quiz',
            'order' => 1,
            'questions' => [
                [
                    'question' => 'Question?',
                    'options' => ['Only one option'],
                    'correct_answers' => [0],
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.sections.items.store', $section->id), $quizData);

        $response->assertSessionHasErrors('questions.0.options');
    }

    public function testQuizQuestionRequiresAtLeastOneCorrectAnswer(): void
    {
        $section = Section::factory()->create();

        $quizData = [
            'type' => 'quiz',
            'title' => 'Test Quiz',
            'order' => 1,
            'questions' => [
                [
                    'question' => 'Question?',
                    'options' => ['A', 'B'],
                    'correct_answers' => [],
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.sections.items.store', $section->id), $quizData);

        $response->assertSessionHasErrors('questions.0.correct_answers');
    }

    public function testQuizSupportsMultipleCorrectAnswers(): void
    {
        $section = Section::factory()->create();

        $quizData = [
            'type' => 'quiz',
            'title' => 'Multi-Answer Quiz',
            'order' => 1,
            'questions' => [
                [
                    'question' => 'Select all programming languages:',
                    'options' => ['PHP', 'HTML', 'JavaScript', 'CSS'],
                    'correct_answers' => [0, 2],
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.sections.items.store', $section->id), $quizData);

        $response->assertRedirect();

        $item = CurriculumItem::where('section_id', $section->id)->first();
        $this->assertNotNull($item);
        $question = $item->quizQuestions->first();
        $this->assertNotNull($question);
        $this->assertEquals([0, 2], $question->correct_answers);
    }

    public function testDeletingSectionCascadesDeleteToQuizzes(): void
    {
        $section = Section::factory()->create();
        $item = CurriculumItem::factory()->create(['section_id' => $section->id, 'type' => 'quiz']);
        $question = QuizQuestion::factory()->create(['curriculum_item_id' => $item->id]);

        $section->delete();

        $this->assertDatabaseMissing('curriculum_items', ['id' => $item->id]);
        $this->assertDatabaseMissing('quiz_questions', ['id' => $question->id]);
    }

    public function testDeletingQuizCascadesDeleteToQuestions(): void
    {
        $item = CurriculumItem::factory()->create(['type' => 'quiz']);
        $question = QuizQuestion::factory()->create(['curriculum_item_id' => $item->id]);

        $item->delete();

        $this->assertDatabaseMissing('quiz_questions', ['id' => $question->id]);
    }

    public function testSectionsAreOrderedCorrectly(): void
    {
        $course = Course::factory()->create();
        Section::factory()->create(['course_id' => $course->id, 'title' => 'Third', 'order' => 3]);
        Section::factory()->create(['course_id' => $course->id, 'title' => 'First', 'order' => 1]);
        Section::factory()->create(['course_id' => $course->id, 'title' => 'Second', 'order' => 2]);

        $sections = $course->sections;

        $this->assertNotNull($sections[0]);
        $this->assertNotNull($sections[1]);
        $this->assertNotNull($sections[2]);
        $this->assertEquals('First', $sections[0]->title);
        $this->assertEquals('Second', $sections[1]->title);
        $this->assertEquals('Third', $sections[2]->title);
    }

    public function testQuizQuestionsAreOrderedCorrectly(): void
    {
        $item = CurriculumItem::factory()->create(['type' => 'quiz']);
        QuizQuestion::factory()->create(['curriculum_item_id' => $item->id, 'question' => 'Third?', 'order' => 2]);
        QuizQuestion::factory()->create(['curriculum_item_id' => $item->id, 'question' => 'First?', 'order' => 0]);
        QuizQuestion::factory()->create(['curriculum_item_id' => $item->id, 'question' => 'Second?', 'order' => 1]);

        $questions = $item->quizQuestions;

        $this->assertNotNull($questions[0]);
        $this->assertNotNull($questions[1]);
        $this->assertNotNull($questions[2]);
        $this->assertEquals('First?', $questions[0]->question);
        $this->assertEquals('Second?', $questions[1]->question);
        $this->assertEquals('Third?', $questions[2]->question);
    }

    public function testCorrectAnswersAreStoredAsIntegers(): void
    {
        $section = Section::factory()->create();

        $quizData = [
            'type' => 'quiz',
            'title' => 'Type Test Quiz',
            'order' => 1,
            'questions' => [
                [
                    'question' => 'Which are correct?',
                    'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                    'correct_answers' => ['0', '2', '3'],
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.sections.items.store', $section->id), $quizData);

        $response->assertRedirect();

        $item = CurriculumItem::where('section_id', $section->id)->first();
        $this->assertNotNull($item);
        $question = $item->quizQuestions->first();
        $this->assertNotNull($question);

        $this->assertIsArray($question->correct_answers);
        foreach ($question->correct_answers as $answer) {
            $this->assertIsInt($answer, 'Correct answer should be stored as integer, not string');
        }
        $this->assertEquals([0, 2, 3], $question->correct_answers);
    }

    public function testCorrectAnswersAreStoredAsIntegersOnUpdate(): void
    {
        $section = Section::factory()->create();
        $item = CurriculumItem::factory()->create([
            'section_id' => $section->id,
            'type' => 'quiz',
            'title' => 'Test Quiz',
        ]);

        QuizQuestion::factory()->create([
            'curriculum_item_id' => $item->id,
            'question' => 'Old question?',
            'options' => ['A', 'B'],
            'correct_answers' => [0],
        ]);

        $updateData = [
            'title' => 'Updated Quiz',
            'order' => 1,
            'questions' => [
                [
                    'question' => 'Updated question?',
                    'options' => ['A', 'B', 'C'],
                    'correct_answers' => ['1', '2'],
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.sections.items.update', [$section->id, $item->id]), $updateData);

        $response->assertRedirect();

        $item->refresh();
        $question = $item->quizQuestions->first();
        $this->assertNotNull($question);

        $this->assertIsArray($question->correct_answers);
        foreach ($question->correct_answers as $answer) {
            $this->assertIsInt($answer, 'Correct answer should be stored as integer after update');
        }
        $this->assertEquals([1, 2], $question->correct_answers);
    }
}
