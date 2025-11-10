<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CurriculumItem>
 */
class CurriculumItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'type' => fake()->randomElement(['video', 'assignment', 'quiz']),
            'title' => fake()->sentence(4),
            'duration_minutes' => fake()->numberBetween(5, 120),
            'order' => 0,
        ];
    }

    /**
     * Indicate that the curriculum item is a video.
     */
    public function video(): static
    {
        return $this->state(fn () => [
            'type' => 'video',
        ]);
    }

    /**
     * Indicate that the curriculum item is an assignment.
     */
    public function assignment(): static
    {
        return $this->state(fn () => [
            'type' => 'assignment',
        ]);
    }

    /**
     * Indicate that the curriculum item is a quiz.
     */
    public function quiz(): static
    {
        return $this->state(fn () => [
            'type' => 'quiz',
        ]);
    }
}
