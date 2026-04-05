<?php

declare(strict_types=1);

namespace Database\Seeders\Skills;

use App\Domains\Skills\Services\RoleMappingService;
use App\Domains\Tenancy\Support\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkillsStarterLibrarySeeder extends Seeder
{
    public function run(int $tenantId): void
    {
        if ($tenantId <= 0) {
            throw new \RuntimeException('Starter library seeding requires a tenant ID.');
        }

        /** @var TenantContext $tenantContext */
        $tenantContext = app(TenantContext::class);
        $tenantContext->setTenantId($tenantId);

        /** @var object{id: int|string}|null $user */
        $user = DB::selectOne(
            'SELECT id
             FROM users
             WHERE tenant_id = ?
             ORDER BY is_admin DESC, id ASC
             LIMIT 1',
            [$tenantId],
        );

        if ($user === null) {
            throw new \RuntimeException('Starter library seeding requires at least one tenant user.');
        }

        app(RoleMappingService::class)->seedStarterLibrary((int) $user->id);
    }
}
