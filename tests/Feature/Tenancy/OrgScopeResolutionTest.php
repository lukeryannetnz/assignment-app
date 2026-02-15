<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Domain\Tenancy\Models\OrgNode;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgScopeResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function testNodeScopeReturnsOnlyActiveDescendantsByDefault(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'is_admin' => true]);

        $root = OrgNode::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => null,
            'depth' => 0,
            'name' => 'Root',
        ]);
        $activeChild = OrgNode::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => $root->id,
            'depth' => 1,
            'node_type' => 'department',
            'name' => 'Active Child',
            'is_active' => true,
        ]);
        $inactiveChild = OrgNode::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => $root->id,
            'depth' => 1,
            'node_type' => 'department',
            'name' => 'Inactive Child',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->getJson("/admin/org-nodes/{$root->id}/scope");

        $response->assertOk();
        $response->assertJsonPath('data.root_node_id', $root->id);
        /** @var list<int> $resolvedNodeIds */
        $resolvedNodeIds = $response->json('data.node_ids');
        $this->assertEqualsCanonicalizing([$root->id, $activeChild->id], $resolvedNodeIds);
        $response->assertJsonMissing(['id' => $inactiveChild->id]);
    }

    public function testNodeScopeCanIncludeInactiveDescendantsAndExcludeSelf(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'is_admin' => true]);

        $root = OrgNode::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => null,
            'depth' => 0,
        ]);
        $child = OrgNode::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => $root->id,
            'depth' => 1,
            'node_type' => 'department',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->getJson(
            "/admin/org-nodes/{$root->id}/scope?include_self=0&active_only=0",
        );

        $response->assertOk();
        $response->assertJsonPath('data.node_ids', [$child->id]);
    }

    public function testBatchScopeResolutionReturnsUnionOfDescendants(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'is_admin' => true]);

        $rootA = OrgNode::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => null,
            'depth' => 0,
            'name' => 'Root A',
        ]);
        $childA = OrgNode::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => $rootA->id,
            'depth' => 1,
            'node_type' => 'department',
            'name' => 'Child A',
        ]);

        $rootB = OrgNode::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => null,
            'depth' => 0,
            'name' => 'Root B',
        ]);
        $childB = OrgNode::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => $rootB->id,
            'depth' => 1,
            'node_type' => 'department',
            'name' => 'Child B',
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/org-scope/resolve', [
            'node_ids' => [$rootA->id, $rootB->id],
        ]);

        $response->assertOk();
        /** @var list<int> $resolvedNodeIds */
        $resolvedNodeIds = $response->json('data.node_ids');
        $this->assertEqualsCanonicalizing([$rootA->id, $childA->id, $rootB->id, $childB->id], $resolvedNodeIds);
    }

    public function testScopeResolutionRejectsCrossTenantNodeInput(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $admin = User::factory()->create(['tenant_id' => $tenantA->id, 'is_admin' => true]);
        $foreignNode = OrgNode::factory()->create([
            'tenant_id' => $tenantB->id,
            'parent_id' => null,
            'depth' => 0,
        ]);

        $singleResponse = $this->actingAs($admin)->getJson("/admin/org-nodes/{$foreignNode->id}/scope");
        $singleResponse->assertStatus(422);

        $batchResponse = $this->actingAs($admin)->postJson('/admin/org-scope/resolve', [
            'node_ids' => [$foreignNode->id],
        ]);

        $batchResponse->assertStatus(422);
    }
}
