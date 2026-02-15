<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CurriculumItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'curriculum_item_id' => CurriculumItem::factory(),
            'question' => fake()->sentence() . '?',
            'options' => [
                fake()->word(),
                fake()->word(),
                fake()->word(),
                fake()->word(),
            ],
            'correct_answers' => [0],
            'order' => 0,
        ];
    }
}
