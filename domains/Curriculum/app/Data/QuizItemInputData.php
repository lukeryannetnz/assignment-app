<?php

declare(strict_types=1);

namespace App\Domains\Curriculum\Data;

use Illuminate\Support\Collection;

final readonly class QuizItemInputData
{
    /**
     * @param Collection<int, QuizQuestionInputData> $questions
     */
    public function __construct(
        public string $title,
        public int $order,
        public Collection $questions,
    ) {
    }
}
