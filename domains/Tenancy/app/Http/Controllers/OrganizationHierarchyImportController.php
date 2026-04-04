<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Http\Controllers;

use App\Domains\Tenancy\Services\OrganizationHierarchyImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nette\ArgumentOutOfRangeException;

class OrganizationHierarchyImportController
{
    public function __construct(private readonly OrganizationHierarchyImportService $importService)
    {
    }

    public function dryRun(Request $request): JsonResponse
    {
        $this->requireUser($request);
        $csvContent = $this->extractCsvContent($request);

        return response()->json([
            'data' => $this->importService->dryRun($csvContent),
        ]);
    }

    public function commit(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        $csvContent = $this->extractCsvContent($request);

        return response()->json([
            'data' => $this->importService->commit($csvContent, (int) $user->id),
        ], 201);
    }

    private function requireUser(Request $request): void
    {
        if ($request->user() === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }
    }

    private function extractCsvContent(Request $request): string
    {
        $validated = $request->validate([
            'csv_file' => ['required', 'file'],
        ]);

        $file = $validated['csv_file'];
        $content = $file->get();
        if (!is_string($content) || trim($content) === '') {
            throw new \InvalidArgumentException('CSV file content is required.');
        }

        return $content;
    }
}
