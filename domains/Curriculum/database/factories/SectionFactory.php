<?php

declare(strict_types=1);

namespace Database\Factories\Curriculum;

use App\Domains\CourseCatalog\Models\Course;
use App\Domains\Curriculum\Models\Section;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    /**
     * @var class-string<Section>
     */
    protected $model = Section::class;

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
                    && isset($attributes['course_id'])
                    && is_numeric($attributes['course_id'])
                ) {
                    $tenantId = Course::query()
                        ->whereKey($attributes['course_id'])
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
            'course_id' => function (mixed $attributes): int {
                $tenantId = is_array($attributes) ? ($attributes['tenant_id'] ?? null) : null;
                if ($tenantId === null) {
                    $tenantId = Tenant::query()->value('id');
                }
                if (!is_numeric($tenantId)) {
                    $tenantId = Tenant::factory()->create()->id;
                }

                return (int) Course::factory()->create(['tenant_id' => $tenantId])->id;
            },
            'title' => fake()->sentence(3),
            'order' => 0,
        ];
    }
}
