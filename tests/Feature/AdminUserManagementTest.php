<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function testUserManagementPageRequiresAuthentication(): void
    {
        $response = $this->get('/admin/users');

        $response->assertRedirect('/login');
    }

    public function testUserManagementPageRequiresAdminRole(): void
    {
        $student = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($student)->get('/admin/users');

        $response->assertStatus(403);
    }

    public function testUserManagementPageDisplaysUsers(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $student = User::factory()->create([
            'name' => 'Test Student',
            'is_admin' => false,
        ]);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
        $response->assertSee('Test Student');
        $response->assertSee($student->email);
    }

    public function testAdminCanPromoteUserToAdmin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $student = User::factory()->create(['is_admin' => false, 'is_student' => true]);

        $response = $this->actingAs($admin)->post("/admin/users/{$student->id}/promote");

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'is_admin' => true,
        ]);
    }

    public function testPromoteRequiresAuthentication(): void
    {
        $student = User::factory()->create(['is_admin' => false]);

        $response = $this->post("/admin/users/{$student->id}/promote");

        $response->assertRedirect('/login');
    }

    public function testPromoteRequiresAdminRole(): void
    {
        $student1 = User::factory()->create(['is_admin' => false]);
        $student2 = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($student1)->post("/admin/users/{$student2->id}/promote");

        $response->assertStatus(403);
    }

    public function testPromoteReturns404ForNonexistentUser(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/admin/users/99999/promote');

        $response->assertNotFound();
    }

    public function testAdminCanDemoteUserFromAdmin(): void
    {
        $admin1 = User::factory()->create(['is_admin' => true]);
        $admin2 = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin1)->post("/admin/users/{$admin2->id}/demote");

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $admin2->id,
            'is_admin' => false,
        ]);
    }

    public function testDemoteRequiresAuthentication(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->post("/admin/users/{$admin->id}/demote");

        $response->assertRedirect('/login');
    }

    public function testDemoteRequiresAdminRole(): void
    {
        $student = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($student)->post("/admin/users/{$admin->id}/demote");

        $response->assertStatus(403);
    }

    public function testAdminCannotDemoteThemselves(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post("/admin/users/{$admin->id}/demote");

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('error');

        // Should still be admin
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_admin' => true,
        ]);
    }

    public function testDemoteReturns404ForNonexistentUser(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/admin/users/99999/demote');

        $response->assertNotFound();
    }

    public function testUserListShowsRoleBadges(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'is_admin' => true,
        ]);
        $student = User::factory()->create([
            'name' => 'Student User',
            'is_admin' => false,
        ]);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
        $response->assertSee('Admin');
        $response->assertSee('Student');
    }

    public function testUserListShowsPromoteButtonForStudents(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $student = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
        $response->assertSee('Promote to Admin');
    }

    public function testUserListShowsDemoteButtonForOtherAdmins(): void
    {
        $admin1 = User::factory()->create(['is_admin' => true]);
        $admin2 = User::factory()->create([
            'name' => 'Other Admin',
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin1)->get('/admin/users');

        $response->assertOk();
        $response->assertSee('Demote from Admin');
    }

    public function testUserListDoesNotShowDemoteButtonForSelf(): void
    {
        $admin = User::factory()->create([
            'name' => 'Current Admin',
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
        // Should see "You" instead of demote button for own row
        $response->assertSee('You');
    }

    public function testUserListIsPaginated(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(15)->create();

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
        // Should see pagination links
        $response->assertSee('page=');
    }

    public function testUserListOrderedByNewestFirst(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $oldUser = User::factory()->create(['name' => 'Old User', 'created_at' => now()->subDays(5)]);
        $newUser = User::factory()->create(['name' => 'New User', 'created_at' => now()]);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertIsString($content);

        // New user should appear before old user in the HTML
        $newUserPos = strpos($content, 'New User');
        $oldUserPos = strpos($content, 'Old User');

        $this->assertNotFalse($newUserPos);
        $this->assertNotFalse($oldUserPos);
        $this->assertLessThan($oldUserPos, $newUserPos);
    }

    public function testPromotionDisplaysSuccessMessage(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $student = User::factory()->create(['name' => 'Test Student']);

        $response = $this->actingAs($admin)->post("/admin/users/{$student->id}/promote");

        $response->assertSessionHas('success');
        $successMessage = session('success');
        $this->assertIsString($successMessage);
        $this->assertTrue(str_contains($successMessage, 'Test Student'));
        $this->assertTrue(str_contains($successMessage, 'promoted'));
    }

    public function testDemotionDisplaysSuccessMessage(): void
    {
        $admin1 = User::factory()->create(['is_admin' => true]);
        $admin2 = User::factory()->create(['name' => 'Test Admin', 'is_admin' => true]);

        $response = $this->actingAs($admin1)->post("/admin/users/{$admin2->id}/demote");

        $response->assertSessionHas('success');
        $successMessage = session('success');
        $this->assertIsString($successMessage);
        $this->assertTrue(str_contains($successMessage, 'Test Admin'));
        $this->assertTrue(str_contains($successMessage, 'demoted'));
    }

    public function testSelfDemotionDisplaysErrorMessage(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post("/admin/users/{$admin->id}/demote");

        $response->assertSessionHas('error');
        $errorMessage = session('error');
        $this->assertIsString($errorMessage);
        $this->assertTrue(str_contains($errorMessage, 'cannot demote yourself'));
    }

    public function testUserCanHaveBothRoles(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $bothRoles = User::factory()->create([
            'name' => 'Both Roles User',
            'is_admin' => true,
            'is_student' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
        $response->assertSee('Both Roles User');
        // Should see both badges
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertGreaterThanOrEqual(2, substr_count($content, 'Admin'));
    }
}
