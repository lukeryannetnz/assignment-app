<?php

declare(strict_types=1);

namespace Tests\Domains\IdentityAccess;

use App\Domains\IdentityAccess\Models\User;
use App\Domains\Tenancy\Data\PlanTier;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Domains\Foundation\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function insertTenantRecord(
        string $name = 'Test Tenant',
        string $status = 'active',
        PlanTier $planTier = PlanTier::EnterprisePilot,
        int $hierarchyDepthLimit = 4,
    ): int {
        $now = now();
        DB::insert(
            'INSERT INTO tenants (name, status, plan_tier, hierarchy_depth_limit, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$name, $status, $planTier->value, $hierarchyDepthLimit, $now, $now],
        );

        return (int) DB::getPdo()->lastInsertId();
    }

    protected function createUserRecord(
        ?int $tenantId = null,
        bool $isAdmin = false,
        bool $isStudent = true,
        ?string $email = null,
        ?string $name = null,
        string $password = 'password',
        ?CarbonImmutable $emailVerifiedAt = null,
        ?CarbonImmutable $createdAt = null,
    ): User {
        $tenantId ??= $this->insertTenantRecord();
        $email ??= Str::lower(Str::uuid()->toString()) . '@example.test';
        $name ??= $isAdmin ? 'Admin User' : 'Student User';
        $createdAt ??= CarbonImmutable::now();

        $rememberToken = substr(md5($email), 0, 10);
        DB::insert(
            'INSERT INTO users
                (tenant_id, name, email, email_verified_at, password, remember_token,
                 is_student, is_admin, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $tenantId,
                $name,
                $email,
                $emailVerifiedAt?->toDateTimeString(),
                bcrypt($password),
                $rememberToken,
                $isStudent,
                $isAdmin,
                $createdAt,
                $createdAt,
            ],
        );

        return $this->makeUser(
            (int) DB::getPdo()->lastInsertId(),
            $tenantId,
            $name,
            $email,
            $password,
            $rememberToken,
            $isStudent,
            $isAdmin,
            $emailVerifiedAt ?? $createdAt,
            $createdAt,
        );
    }

    protected function userExistsById(int $id): bool
    {
        $row = DB::selectOne(
            'SELECT 1 AS present
             FROM users
             WHERE id = ?
             LIMIT 1',
            [$id],
        );

        return $row !== null;
    }

    protected function findUserById(int $id): User
    {
        /** @var object{
         *     id: int,
         *     tenant_id: int|null,
         *     name: string,
         *     email: string,
         *     email_verified_at: string|null,
         *     is_student: int|bool,
         *     is_admin: int|bool,
         *     created_at: string,
         *     updated_at: string
         * }|null $row
         */
        $row = DB::selectOne(
            'SELECT id, tenant_id, name, email, email_verified_at, is_student, is_admin, created_at, updated_at
             FROM users
             WHERE id = ?
             LIMIT 1',
            [$id],
        );

        if ($row === null) {
            throw new \RuntimeException("User {$id} was not found.");
        }

        $user = new User();
        $user->forceFill([
            'id' => (int) $row->id,
            'tenant_id' => $row->tenant_id !== null ? (int) $row->tenant_id : null,
            'name' => (string) $row->name,
            'email' => (string) $row->email,
            'email_verified_at' => $row->email_verified_at !== null
                ? CarbonImmutable::parse((string) $row->email_verified_at)
                : null,
            'password' => '',
            'remember_token' => null,
            'is_student' => (bool) $row->is_student,
            'is_admin' => (bool) $row->is_admin,
            'created_at' => CarbonImmutable::parse((string) $row->created_at),
            'updated_at' => CarbonImmutable::parse((string) $row->updated_at),
        ]);
        $user->exists = true;

        return $user;
    }

    protected function lastInsertId(): int
    {
        return (int) DB::getPdo()->lastInsertId();
    }

    private function makeUser(
        int $id,
        ?int $tenantId,
        string $name,
        string $email,
        string $password,
        string $rememberToken,
        bool $isStudent,
        bool $isAdmin,
        ?CarbonImmutable $emailVerifiedAt,
        CarbonImmutable $createdAt,
    ): User {
        $user = new User();
        $user->forceFill([
            'id' => $id,
            'tenant_id' => $tenantId,
            'name' => $name,
            'email' => $email,
            'email_verified_at' => $emailVerifiedAt,
            'password' => bcrypt($password),
            'remember_token' => $rememberToken,
            'is_student' => $isStudent,
            'is_admin' => $isAdmin,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
        $user->exists = true;

        return $user;
    }
}
