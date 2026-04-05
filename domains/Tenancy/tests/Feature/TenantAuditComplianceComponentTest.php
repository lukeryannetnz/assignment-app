<?php

declare(strict_types=1);

namespace Tests\Domains\Tenancy\Feature;

use App\Domains\IdentityAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Domains\Foundation\TestCase;

class TenantAuditComplianceComponentTest extends TestCase
{
    use RefreshDatabase;

    public function testAuditRouteRequiresAuthenticationAndAdminRole(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Tenant');
        $member = $this->createUserRecord($tenantId, false, 'member-audit@example.test');

        $unauthenticatedResponse = $this->get('/admin/tenancy/audit');
        $unauthenticatedResponse->assertRedirect('/login');

        $forbiddenResponse = $this->actingAs($member)->get('/admin/tenancy/audit');
        $forbiddenResponse->assertForbidden();
    }

    public function testAuditRouteReturnsTenantScopedLogsAndComplianceSummary(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Tenant');
        $otherTenantId = $this->insertTenantRecord('Other Tenant');
        $admin = $this->createUserRecord($tenantId, true, 'audit-admin@example.test');

        $this->insertAuditLog($tenantId, (int) $admin->id, 'tenant_updated', 'tenant', $tenantId, [
            'name' => 'Acme Tenant',
        ]);
        $this->insertAuditLog($otherTenantId, null, 'tenant_updated', 'tenant', $otherTenantId, [
            'name' => 'Other Tenant',
        ]);

        $response = $this->actingAs($admin)->getJson('/admin/tenancy/audit');

        $response->assertOk();
        $response->assertJsonPath('data.logs.0.tenant_id', $tenantId);
        $response->assertJsonPath('data.logs.0.action', 'tenant_updated');
        $response->assertJsonPath('data.logs.0.metadata.name', 'Acme Tenant');
        $response->assertJsonPath('data.compliance.minimum_retention_months', 12);
        $response->assertJsonPath(
            'data.compliance.access_scope',
            'Authenticated tenant admins scoped to their current tenant context.',
        );

        $payload = $response->json('data.logs');
        $this->assertIsArray($payload);
        $this->assertCount(1, $payload);
    }

    private function insertTenantRecord(string $name): int
    {
        DB::insert(
            'INSERT INTO tenants (name, status, plan_tier, hierarchy_depth_limit, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$name, 'active', 'enterprise_pilot', 4, now(), now()],
        );

        return $this->lastInsertId();
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function insertAuditLog(
        int $tenantId,
        ?int $actorUserId,
        string $action,
        string $auditableType,
        int $auditableId,
        array $metadata,
    ): void {
        DB::insert(
            'INSERT INTO tenant_audit_logs
                (tenant_id, actor_user_id, action, auditable_type, auditable_id, metadata, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $tenantId,
                $actorUserId,
                $action,
                $auditableType,
                $auditableId,
                json_encode($metadata, JSON_THROW_ON_ERROR),
                now(),
                now(),
            ],
        );
    }

    private function createUserRecord(int $tenantId, bool $isAdmin, string $email): User
    {
        DB::insert(
            'INSERT INTO users
                (tenant_id, name, email, email_verified_at, password, remember_token,
                 is_student, is_admin, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $tenantId,
                $isAdmin ? 'Admin User' : 'Member User',
                $email,
                now(),
                bcrypt('password'),
                substr(md5($email), 0, 10),
                true,
                $isAdmin,
                now(),
                now(),
            ],
        );

        return $this->makeUser((int) $this->lastInsertId(), $tenantId, $isAdmin, $email);
    }

    private function makeUser(int $id, int $tenantId, bool $isAdmin, string $email): User
    {
        $user = new User();
        $user->forceFill([
            'id' => $id,
            'tenant_id' => $tenantId,
            'name' => $isAdmin ? 'Admin User' : 'Member User',
            'email' => $email,
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'remember_token' => substr(md5($email), 0, 10),
            'is_student' => true,
            'is_admin' => $isAdmin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user->exists = true;

        return $user;
    }

    private function lastInsertId(): int
    {
        return (int) DB::getPdo()->lastInsertId();
    }
}
