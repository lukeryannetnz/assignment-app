<?php

declare(strict_types=1);

namespace Database\Factories\CourseCatalog;

use App\Domain\CourseCatalog\Models\Course;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Course>
     */
    protected $model = Course::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::query()->value('id') ?? Tenant::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
        ];
    }
}
