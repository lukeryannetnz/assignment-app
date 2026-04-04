<?php

declare(strict_types=1);

namespace App\Domains\IdentityAccess\Services;

use App\Domains\IdentityAccess\Data\UserData;
use App\Domains\IdentityAccess\Models\User;
use App\Domains\Tenancy\Data\PlanTier;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class IdentityAccessService
{
    /**
     * @return LengthAwarePaginator<int, UserData>
     */
    public function paginateUsers(int $tenantId, int $perPage = 5, ?int $page = null): LengthAwarePaginator
    {
        $resolvedPage = max($page ?? Paginator::resolveCurrentPage('page'), 1);
        $offset = ($resolvedPage - 1) * $perPage;

        /** @var object{aggregate: int|string}|null $countRow */
        $countRow = DB::selectOne(
            'SELECT COUNT(*) AS aggregate
             FROM users
             WHERE tenant_id = ?',
            [$tenantId],
        );

        /** @var list<object{
         *     id: int,
         *     tenant_id: int|null,
         *     name: string,
         *     email: string,
         *     email_verified_at: string|null,
         *     is_student: int|bool,
         *     is_admin: int|bool,
         *     created_at: string,
         *     updated_at: string
         * }> $rows
         */
        $rows = DB::select(
            'SELECT id, tenant_id, name, email, email_verified_at, is_student, is_admin, created_at, updated_at
             FROM users
             WHERE tenant_id = ?
             ORDER BY created_at DESC, id DESC
             LIMIT ? OFFSET ?',
            [$tenantId, $perPage, $offset],
        );

        $items = array_map(static fn (object $row): UserData => UserData::fromRow($row), $rows);

        return new LengthAwarePaginator(
            $items,
            $countRow !== null ? (int) $countRow->aggregate : 0,
            $perPage,
            $resolvedPage,
            [
                'path' => Paginator::resolveCurrentPath(),
                'query' => request()->query(),
            ],
        );
    }

    public function findUser(int $tenantId, int $userId): UserData
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
             WHERE tenant_id = ?
               AND id = ?
             LIMIT 1',
            [$tenantId, $userId],
        );

        if ($row === null) {
            throw new NotFoundHttpException("User {$userId} was not found.");
        }

        return UserData::fromRow($row);
    }

    public function createUser(
        ?int $tenantId,
        string $name,
        string $email,
        string $password,
        bool $isStudent = true,
        bool $isAdmin = false,
        ?CarbonImmutable $emailVerifiedAt = null,
    ): UserData {
        $now = now();
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
                Hash::make($password),
                substr(md5($email), 0, 10),
                $isStudent,
                $isAdmin,
                $now,
                $now,
            ],
        );

        return $this->findUserByEmail($email);
    }

    public function registerUser(string $name, string $email, string $password): UserData
    {
        return DB::transaction(function () use ($name, $email, $password): UserData {
            $now = now();

            DB::insert(
                'INSERT INTO tenants (name, status, plan_tier, hierarchy_depth_limit, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    sprintf('%s Tenant', $name),
                    'active',
                    PlanTier::EnterprisePilot->value,
                    4,
                    $now,
                    $now,
                ],
            );

            $tenantId = (int) DB::getPdo()->lastInsertId();

            return $this->createUser(
                tenantId: $tenantId,
                name: $name,
                email: $email,
                password: $password,
                isStudent: true,
                isAdmin: false,
                emailVerifiedAt: CarbonImmutable::now(),
            );
        });
    }

    public function promoteUserToAdmin(int $tenantId, int $userId): UserData
    {
        $this->updateUserFlag($tenantId, $userId, true);

        return $this->findUser($tenantId, $userId);
    }

    public function demoteUserFromAdmin(int $tenantId, int $userId): UserData
    {
        $this->updateUserFlag($tenantId, $userId, false);

        return $this->findUser($tenantId, $userId);
    }

    public function markEmailVerified(int $userId): UserData
    {
        DB::update(
            'UPDATE users
             SET email_verified_at = ?, updated_at = ?
             WHERE id = ?',
            [now(), now(), $userId],
        );

        return $this->findUserById($userId);
    }

    public function updateProfile(int $userId, string $name, string $email): UserData
    {
        $currentUser = $this->findUserById($userId);
        $emailVerifiedAt = $currentUser->email === $email
            ? $currentUser->email_verified_at?->toDateTimeString()
            : null;

        DB::update(
            'UPDATE users
             SET name = ?, email = ?, email_verified_at = ?, updated_at = ?
             WHERE id = ?',
            [$name, $email, $emailVerifiedAt, now(), $userId],
        );

        return $this->findUserById($userId);
    }

    public function updatePassword(int $userId, string $password): void
    {
        DB::update(
            'UPDATE users
             SET password = ?, updated_at = ?
             WHERE id = ?',
            [Hash::make($password), now(), $userId],
        );
    }

    public function resetPassword(int $userId, string $password): void
    {
        DB::update(
            'UPDATE users
             SET password = ?, remember_token = ?, updated_at = ?
             WHERE id = ?',
            [Hash::make($password), Str::random(60), now(), $userId],
        );
    }

    public function deleteUser(int $tenantId, int $userId): void
    {
        $affected = DB::delete(
            'DELETE FROM users
             WHERE tenant_id = ?
               AND id = ?',
            [$tenantId, $userId],
        );

        if ($affected === 0) {
            throw new NotFoundHttpException("User {$userId} was not found.");
        }
    }

    public function makeAuthenticatableUser(UserData $userData): User
    {
        $user = new User();
        $user->forceFill([
            'id' => $userData->id,
            'tenant_id' => $userData->tenant_id,
            'name' => $userData->name,
            'email' => $userData->email,
            'email_verified_at' => $userData->email_verified_at,
            'password' => '',
            'remember_token' => null,
            'is_student' => $userData->is_student,
            'is_admin' => $userData->is_admin,
            'created_at' => $userData->created_at,
            'updated_at' => $userData->updated_at,
        ]);
        $user->exists = true;

        return $user;
    }

    public function findUserByEmail(string $email): UserData
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
             WHERE email = ?
             LIMIT 1',
            [$email],
        );

        if ($row === null) {
            throw new RuntimeException("User {$email} was not found after insert.");
        }

        return UserData::fromRow($row);
    }

    private function updateUserFlag(int $tenantId, int $userId, bool $isAdmin): void
    {
        $affected = DB::update(
            'UPDATE users
             SET is_admin = ?, updated_at = ?
             WHERE tenant_id = ?
               AND id = ?',
            [$isAdmin, now(), $tenantId, $userId],
        );

        if ($affected === 0) {
            throw new NotFoundHttpException("User {$userId} was not found.");
        }
    }

    private function findUserById(int $userId): UserData
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
            [$userId],
        );

        if ($row === null) {
            throw new NotFoundHttpException("User {$userId} was not found.");
        }

        return UserData::fromRow($row);
    }
}
