<?php

declare(strict_types=1);

namespace Database\Factories\Tenancy;

use App\Domains\Tenancy\Data\PlanTier;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * @var class-string<Tenant>
     */
    protected $model = Tenant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Tenant',
            'status' => 'active',
            'plan_tier' => PlanTier::EnterprisePilot,
            'hierarchy_depth_limit' => 4,
        ];
    }
}
