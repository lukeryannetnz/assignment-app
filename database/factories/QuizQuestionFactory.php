<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Curriculum\CurriculumItem;
use App\Models\Tenancy\Tenant;
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
            'tenant_id' => function (mixed $attributes): int {
                if (
                    is_array($attributes)
                    && isset($attributes['curriculum_item_id'])
                    && is_numeric($attributes['curriculum_item_id'])
                ) {
                    $tenantId = CurriculumItem::query()
                        ->whereKey($attributes['curriculum_item_id'])
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
            'curriculum_item_id' => function (mixed $attributes): int {
                $tenantId = is_array($attributes) ? ($attributes['tenant_id'] ?? null) : null;
                if ($tenantId === null) {
                    $tenantId = Tenant::query()->value('id');
                }
                if (!is_numeric($tenantId)) {
                    $tenantId = Tenant::factory()->create()->id;
                }

                return (int) CurriculumItem::factory()->create(['tenant_id' => $tenantId])->id;
            },
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
