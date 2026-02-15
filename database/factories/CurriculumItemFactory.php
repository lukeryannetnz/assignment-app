<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Curriculum\CurriculumItem;
use App\Models\Curriculum\Section;
use App\Models\Tenancy\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurriculumItem>
 */
class CurriculumItemFactory extends Factory
{
    /**
     * @var class-string<CurriculumItem>
     */
    protected $model = CurriculumItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => function (mixed $attributes): int {
                if (
                    is_array($attributes)
                    && isset($attributes['section_id'])
                    && is_numeric($attributes['section_id'])
                ) {
                    $tenantId = Section::query()
                        ->whereKey($attributes['section_id'])
                        ->value('tenant_id');
                    if (is_numeric($tenantId)) {
                        return (int) $tenantId;
                    }
                }

                $existingTenantId = Tenant::query()->value('id');
                if (is_numeric($existingTenantId)) {
                    return (int) $existingTenantId;
                }

                return (int) Tenant::factory()->create()->id;
            },
            'section_id' => function (mixed $attributes): int {
                $tenantId = is_array($attributes) ? ($attributes['tenant_id'] ?? null) : null;
                if ($tenantId === null) {
                    $tenantId = Tenant::query()->value('id');
                }
                if (!is_numeric($tenantId)) {
                    $tenantId = Tenant::factory()->create()->id;
                }

                return (int) Section::factory()->create(['tenant_id' => $tenantId])->id;
            },
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
