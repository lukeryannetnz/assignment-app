<?php

declare(strict_types=1);

namespace Database\Factories\Tenancy;

use App\Domains\Tenancy\Data\OrgNodeType;
use App\Domains\Tenancy\Models\OrgNode;
use App\Domains\Tenancy\Models\Tenant;
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
            'node_type' => OrgNodeType::Company->value,
            'name' => fake()->company(),
            'depth' => 0,
            'is_active' => true,
        ];
    }
}
