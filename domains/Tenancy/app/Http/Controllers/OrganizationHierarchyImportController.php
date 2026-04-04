<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Http\Controllers;

use App\Domains\Tenancy\Services\OrganizationHierarchyImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Nette\ArgumentOutOfRangeException;

class OrganizationHierarchyImportController
{
    public function __construct(private readonly OrganizationHierarchyImportService $importService)
    {
    }

    public function dryRun(Request $request): JsonResponse|RedirectResponse
    {
        $this->requireUser($request);
        $csvContent = $this->extractCsvContent($request);
        $preview = $this->importService->dryRun($csvContent);

        if (!$this->shouldReturnHtml($request)) {
            return response()->json([
                'data' => $preview,
            ]);
        }

        $request->session()->put('tenancy.org_import.csv_content', $csvContent);
        $request->session()->put('tenancy.org_import.preview', $preview->toArray());
        $request->session()->forget('tenancy.org_import.result');

        return redirect()
            ->route('tenancy.admin.org-nodes.index')
            ->with(
                'status',
                $preview->canCommit()
                    ? 'CSV dry-run completed. Review the proposed hierarchy below.'
                    : 'CSV dry-run found blocking issues. Review the errors below.',
            );
    }

    public function commit(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        $shouldReturnHtml = $this->shouldReturnHtml($request);
        $csvContent = $this->extractCsvContent($request, allowSessionPreview: $shouldReturnHtml);
        $result = $this->importService->commit($csvContent, (int) $user->id);

        if (!$shouldReturnHtml) {
            return response()->json([
                'data' => $result,
            ], 201);
        }

        $request->session()->forget('tenancy.org_import.csv_content');
        $request->session()->forget('tenancy.org_import.preview');
        $request->session()->put('tenancy.org_import.result', $result->toArray());

        return redirect()
            ->route('tenancy.admin.org-nodes.index')
            ->with('status', sprintf('CSV import committed successfully. %d nodes created.', $result->importedCount));
    }

    private function requireUser(Request $request): void
    {
        if ($request->user() === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }
    }

    private function extractCsvContent(Request $request, bool $allowSessionPreview = false): string
    {
        $validated = $request->validate([
            'csv_file' => [$allowSessionPreview ? 'nullable' : 'required', 'file'],
        ]);

        if (array_key_exists('csv_file', $validated)) {
            $file = $validated['csv_file'];
            $content = $file->get();
            if (!is_string($content) || trim($content) === '') {
                throw new \InvalidArgumentException('CSV file content is required.');
            }

            return $content;
        }

        if ($allowSessionPreview) {
            /** @var string|null $sessionContent */
            $sessionContent = $request->session()->get('tenancy.org_import.csv_content');
            if (is_string($sessionContent) && trim($sessionContent) !== '') {
                return $sessionContent;
            }
        }

        throw new \InvalidArgumentException('CSV file content is required.');
    }

    private function shouldReturnHtml(Request $request): bool
    {
        return $request->string('ui_form')->toString() === '1';
    }
}
