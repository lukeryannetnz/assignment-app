<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Http\Controllers;

use App\Domains\Tenancy\Services\TenantAuditLogService;
use App\Domains\Tenancy\Support\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantAuditLogController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuditLogService $auditLogService,
    ) {
    }

    public function index(Request $request): JsonResponse|View
    {
        $tenantId = $this->tenantContext->requireTenantId();
        $logs = $this->auditLogService->listRecentLogs($tenantId);
        $compliance = $this->auditLogService->complianceSummary();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => [
                    'logs' => $logs,
                    'compliance' => $compliance,
                ],
            ]);
        }

        return view('tenancy::admin.audit.index', [
            'logs' => $logs,
            'compliance' => $compliance,
        ]);
    }
}
