<?php

declare(strict_types=1);

namespace Tests\Domains\IdentityAccess\Feature\Auth;

use App\Domains\Tenancy\Data\OrgNodeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Domains\Foundation\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function testRegistrationScreenCanBeRendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function testNewUsersCanRegister(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('course-catalog.dashboard', absolute: false));

        /** @var object{id: int, tenant_id: int} $user */
        $user = DB::selectOne(
            'SELECT id, tenant_id
             FROM users
             WHERE email = ?
             LIMIT 1',
            ['test@example.com'],
        );

        /** @var object{parent_id: int|null, node_type: string, name: string, depth: int, is_active: int|bool} $rootCompany */
        $rootCompany = DB::selectOne(
            'SELECT parent_id, node_type, name, depth, is_active
             FROM org_nodes
             WHERE tenant_id = ?
               AND parent_id IS NULL
             LIMIT 1',
            [(int) $user->tenant_id],
        );

        $this->assertNull($rootCompany->parent_id);
        $this->assertSame(OrgNodeType::Company->value, $rootCompany->node_type);
        $this->assertSame('Test User Tenant', $rootCompany->name);
        $this->assertSame(0, (int) $rootCompany->depth);
        $this->assertTrue((bool) $rootCompany->is_active);
    }
}
