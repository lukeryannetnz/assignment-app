<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Services;

use App\Domains\Tenancy\Data\OrgNodeType;
use App\Domains\Tenancy\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class RootCompanyOrgNodeService
{
    private bool $resolved = false;

    private ?string $rootCompanyName = null;

    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function rootCompanyName(): ?string
    {
        if ($this->resolved) {
            return $this->rootCompanyName;
        }

        $this->resolved = true;

        $tenantId = $this->tenantContext->tenantId();
        if ($tenantId === null) {
            return null;
        }

        /** @var object{name: string}|null $row */
        $row = DB::selectOne(
            'SELECT name
             FROM org_nodes
             WHERE tenant_id = ?
               AND parent_id IS NULL
               AND node_type = ?
             ORDER BY id ASC
             LIMIT 1',
            [$tenantId, OrgNodeType::Company->value],
        );

        $this->rootCompanyName = $row !== null ? (string) $row->name : null;

        return $this->rootCompanyName;
    }
}
