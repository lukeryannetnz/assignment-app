<?php

declare(strict_types=1);

namespace App\Domains\Curriculum\Data;

use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class CurriculumItemData implements JsonSerializable
{
    /**
     * @param Collection<int, QuizQuestionData> $quizQuestions
     */
    public function __construct(
        public int $id,
        public int $tenant_id,
        public int $section_id,
        public CurriculumItemType $type,
        public string $title,
        public int $duration_minutes,
        public int $order,
        public ?string $video_path,
        public ?string $assignment_content,
        public Collection $quizQuestions,
    ) {
    }

    /**
     * @return array{
     *     id: int,
     *     tenant_id: int,
     *     section_id: int,
     *     type: string,
     *     title: string,
     *     duration_minutes: int,
     *     order: int,
     *     video_path: string|null,
     *     assignment_content: string|null,
     *     quiz_questions: list<array{
     *         id: int,
     *         tenant_id: int,
     *         curriculum_item_id: int,
     *         question: string,
     *         options: list<string>,
     *         correct_answers: list<int>,
     *         order: int
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'section_id' => $this->section_id,
            'type' => $this->type->value,
            'title' => $this->title,
            'duration_minutes' => $this->duration_minutes,
            'order' => $this->order,
            'video_path' => $this->video_path,
            'assignment_content' => $this->assignment_content,
            'quiz_questions' => array_values(
                $this->quizQuestions
                    ->map(static fn (QuizQuestionData $question): array => $question->toArray())
                    ->all(),
            ),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     tenant_id: int,
     *     section_id: int,
     *     type: string,
     *     title: string,
     *     duration_minutes: int,
     *     order: int,
     *     video_path: string|null,
     *     assignment_content: string|null,
     *     quiz_questions: list<array{
     *         id: int,
     *         tenant_id: int,
     *         curriculum_item_id: int,
     *         question: string,
     *         options: list<string>,
     *         correct_answers: list<int>,
     *         order: int
     *     }>
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
