<?php

declare(strict_types=1);

namespace Database\Factories\Tenancy;

use App\Models\Tenancy\OrgNode;
use App\Models\Tenancy\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrgNode>
 */
class OrgNodeFactory extends Factory
{
    /**
     * @var class-string<OrgNode>
     */
    protected $model = OrgNode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::query()->value('id') ?? Tenant::factory(),
            'parent_id' => null,
            'node_type' => 'company',
            'name' => fake()->company(),
            'depth' => 0,
            'is_active' => true,
        ];
    }
}
