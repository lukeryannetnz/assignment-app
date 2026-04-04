<?php

declare(strict_types=1);

namespace App\Domains\Curriculum\Data;

final readonly class QuizQuestionInputData
{
    /**
     * @param list<string> $options
     * @param list<int> $correctAnswers
     */
    public function __construct(
        public string $question,
        public array $options,
        public array $correctAnswers,
    ) {
    }
}
