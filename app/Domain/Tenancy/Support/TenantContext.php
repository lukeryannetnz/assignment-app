<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Support;

use RuntimeException;

class TenantContext
{
    private ?int $tenantId = null;

    public function setTenantId(int $tenantId): void
    {
        if ($tenantId <= 0) {
            throw new RuntimeException('Tenant ID must be a positive integer.');
        }

        $this->tenantId = $tenantId;
    }

    public function tenantId(): ?int
    {
        return $this->tenantId;
    }

    public function requireTenantId(): int
    {
        if ($this->tenantId === null) {
            throw new RuntimeException('Tenant context has not been initialized for this request.');
        }

        return $this->tenantId;
    }
}
