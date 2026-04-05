<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Http\Controllers;

use App\Domains\Tenancy\Services\PilotReadinessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PilotReadinessController
{
    public function __construct(private readonly PilotReadinessService $pilotReadinessService)
    {
    }

    public function show(Request $request): JsonResponse|View
    {
        $summary = $this->pilotReadinessService->summary();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $summary,
            ]);
        }

        return view('tenancy::admin.pilot-readiness.show', [
            'summary' => $summary,
        ]);
    }
}
