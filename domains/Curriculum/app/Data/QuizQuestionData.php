<?php

declare(strict_types=1);

namespace App\Domains\Curriculum\Data;

use JsonSerializable;

final readonly class QuizQuestionData implements JsonSerializable
{
    /**
     * @param list<string> $options
     * @param list<int> $correctAnswers
     */
    public function __construct(
        public int $id,
        public int $tenant_id,
        public int $curriculum_item_id,
        public string $question,
        public array $options,
        public array $correctAnswers,
        public int $order,
    ) {
    }

    /**
     * @return array{
     *     id: int,
     *     tenant_id: int,
     *     curriculum_item_id: int,
     *     question: string,
     *     options: list<string>,
     *     correct_answers: list<int>,
     *     order: int
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'curriculum_item_id' => $this->curriculum_item_id,
            'question' => $this->question,
            'options' => $this->options,
            'correct_answers' => $this->correctAnswers,
            'order' => $this->order,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     tenant_id: int,
     *     curriculum_item_id: int,
     *     question: string,
     *     options: list<string>,
     *     correct_answers: list<int>,
     *     order: int
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
