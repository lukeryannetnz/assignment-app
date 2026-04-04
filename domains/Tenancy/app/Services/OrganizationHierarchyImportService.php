<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Services;

use App\Domains\Tenancy\Data\OrgNodeType;
use App\Domains\Tenancy\Data\OrganizationHierarchyImportCommitResult;
use App\Domains\Tenancy\Data\OrganizationHierarchyImportError;
use App\Domains\Tenancy\Data\OrganizationHierarchyImportPreview;
use App\Domains\Tenancy\Data\OrganizationHierarchyImportRow;
use App\Domains\Tenancy\Data\ProvisionedOrgNode;
use App\Domains\Tenancy\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * @phpstan-type ImportRow array{
 *     row_number: int,
 *     row_key: string,
 *     parent_row_key: string|null,
 *     node_type: OrgNodeType|null,
 *     name: string,
 *     resolved_depth?: int
 * }
 */
class OrganizationHierarchyImportService
{
    private const EXPECTED_HEADERS = ['row_key', 'parent_row_key', 'node_type', 'name'];

    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function dryRun(string $csvContent): OrganizationHierarchyImportPreview
    {
        return $this->analyzeCsv($csvContent)->preview;
    }

    public function commit(string $csvContent, int $actorUserId): OrganizationHierarchyImportCommitResult
    {
        $analysis = $this->analyzeCsv($csvContent);
        if (!$analysis->preview->canCommit()) {
            throw ValidationException::withMessages([
                'csv_file' => array_map(
                    static fn (OrganizationHierarchyImportError $error): string => sprintf(
                        'Row %d [%s]: %s',
                        $error->rowNumber,
                        $error->field,
                        $error->message,
                    ),
                    $analysis->preview->errors,
                ),
            ]);
        }

        $tenantId = $this->tenantContext->requireTenantId();

        /** @var list<ProvisionedOrgNode> $createdNodes */
        $createdNodes = DB::transaction(function () use ($actorUserId, $analysis, $tenantId): array {
            /** @var array<string, int> $rowKeyToNodeId */
            $rowKeyToNodeId = [];
            /** @var list<ProvisionedOrgNode> $createdNodes */
            $createdNodes = [];
            $timestampIndex = 0;

            /** @var array<int, list<ImportRow>> $rowsByDepth */
            $rowsByDepth = [];
            foreach ($analysis->orderedRows as $row) {
                $resolvedDepth = $row['resolved_depth'] ?? null;
                if ($resolvedDepth === null) {
                    throw new RuntimeException('Import analysis must resolve row depths before commit.');
                }

                $rowsByDepth[$resolvedDepth] ??= [];
                $rowsByDepth[$resolvedDepth][] = $row;
            }

            ksort($rowsByDepth);

            foreach ($rowsByDepth as $rowsAtDepth) {
                $batch = [];
                foreach ($rowsAtDepth as $row) {
                    $parentId = $analysis->rootNodeId;
                    if ($row['parent_row_key'] !== null) {
                        $parentId = $rowKeyToNodeId[$row['parent_row_key']] ?? null;
                    }

                    if ($parentId === null || $row['node_type'] === null || !isset($row['resolved_depth'])) {
                        throw new RuntimeException(
                            'Import analysis must resolve parent IDs, node types, and row depths before commit.',
                        );
                    }

                    $timestamp = now()->copy()->addMicroseconds($timestampIndex)->format('Y-m-d H:i:s.u');
                    $timestampIndex++;

                    $batch[] = [
                        'row_key' => $row['row_key'],
                        'tenant_id' => $tenantId,
                        'parent_id' => $parentId,
                        'node_type' => $row['node_type']->value,
                        'name' => $row['name'],
                        'depth' => $row['resolved_depth'],
                        'is_active' => true,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }

                $insertedNodes = $this->insertNodeBatch($batch);
                $this->insertAuditBatch($tenantId, $actorUserId, $insertedNodes);

                foreach ($insertedNodes as $insertedNode) {
                    $rowKeyToNodeId[$insertedNode['row_key']] = $insertedNode['node']->id;
                    $createdNodes[] = $insertedNode['node'];
                }
            }

            return $createdNodes;
        });

        return new OrganizationHierarchyImportCommitResult(
            importedCount: count($createdNodes),
            createdNodes: $createdNodes,
        );
    }

    /**
     * @return object{
     *     preview: OrganizationHierarchyImportPreview,
     *     orderedRows: list<ImportRow>,
     *     rootNodeId: int
     * }
     */
    private function analyzeCsv(string $csvContent): object
    {
        $tenantId = $this->tenantContext->requireTenantId();
        [$rootNodeId, $rootDepth] = $this->findRootNode($tenantId);

        /** @var list<OrganizationHierarchyImportError> $errors */
        $errors = [];
        /** @var list<ImportRow> $rows */
        $rows = [];
        /** @var array<string, int> $rowKeyToIndex */
        $rowKeyToIndex = [];

        $parsed = $this->parseCsvRows($csvContent);
        if ($parsed['header_error'] !== null) {
            $errors[] = $parsed['header_error'];
        }

        foreach ($parsed['rows'] as $row) {
            $rowKey = trim($row[0]);
            $parentRowKey = trim($row[1]);
            $nodeTypeValue = trim($row[2]);
            $name = trim($row[3]);
            $nodeType = $nodeTypeValue !== '' ? OrgNodeType::tryFrom($nodeTypeValue) : null;

            if ($rowKey === '') {
                $errors[] = new OrganizationHierarchyImportError(
                    $parsed['row_numbers'][$row['index']],
                    'row_key',
                    'Row key is required.',
                );
            } elseif (array_key_exists($rowKey, $rowKeyToIndex)) {
                $errors[] = new OrganizationHierarchyImportError(
                    $parsed['row_numbers'][$row['index']],
                    'row_key',
                    'Row key must be unique within the import.',
                );
            } else {
                $rowKeyToIndex[$rowKey] = count($rows);
            }

            if ($name === '') {
                $errors[] = new OrganizationHierarchyImportError(
                    $parsed['row_numbers'][$row['index']],
                    'name',
                    'Name is required.',
                );
            }

            if ($nodeType === null) {
                $errors[] = new OrganizationHierarchyImportError(
                    $parsed['row_numbers'][$row['index']],
                    'node_type',
                    'Node type must be one of business_unit, department, or team.',
                );
            } elseif ($nodeType === OrgNodeType::Company) {
                $errors[] = new OrganizationHierarchyImportError(
                    $parsed['row_numbers'][$row['index']],
                    'node_type',
                    'Company rows cannot be imported because the tenant root company already exists.',
                );
            }

            if ($parentRowKey !== '' && $parentRowKey === $rowKey) {
                $errors[] = new OrganizationHierarchyImportError(
                    $parsed['row_numbers'][$row['index']],
                    'parent_row_key',
                    'Parent row key cannot reference the same row.',
                );
            }

            $rows[] = [
                'row_number' => $parsed['row_numbers'][$row['index']],
                'row_key' => $rowKey,
                'parent_row_key' => $parentRowKey !== '' ? $parentRowKey : null,
                'node_type' => $nodeType,
                'name' => $name,
            ];
        }

        if ($rows === [] && $parsed['header_error'] === null) {
            $errors[] = new OrganizationHierarchyImportError(0, 'csv_file', 'CSV must contain at least one data row.');
        }

        foreach ($rows as $row) {
            if ($row['parent_row_key'] !== null && !array_key_exists($row['parent_row_key'], $rowKeyToIndex)) {
                $errors[] = new OrganizationHierarchyImportError(
                    $row['row_number'],
                    'parent_row_key',
                    sprintf('Parent row key "%s" was not found in the import.', $row['parent_row_key']),
                );
            }
        }

        $orderedRows = $this->resolveRowDepthsAndOrdering($rows, $rowKeyToIndex, $rootDepth, $tenantId, $errors);

        $previewRows = array_map(
            static fn (array $row): OrganizationHierarchyImportRow => new OrganizationHierarchyImportRow(
                rowNumber: $row['row_number'],
                rowKey: $row['row_key'],
                parentRowKey: $row['parent_row_key'],
                nodeType: $row['node_type'],
                name: $row['name'],
                resolvedDepth: $row['resolved_depth'] ?? null,
            ),
            $rows,
        );

        $preview = new OrganizationHierarchyImportPreview(
            rows: $previewRows,
            errors: $errors,
        );

        return (object) [
            'preview' => $preview,
            'orderedRows' => $orderedRows,
            'rootNodeId' => $rootNodeId,
        ];
    }

    /**
     * @param  list<ImportRow>  $rows
     * @param  array<string, int>  $rowKeyToIndex
     * @param  list<OrganizationHierarchyImportError>  $errors
     * @return list<ImportRow>
     */
    private function resolveRowDepthsAndOrdering(
        array &$rows,
        array $rowKeyToIndex,
        int $rootDepth,
        int $tenantId,
        array &$errors,
    ): array {
        $tenant = $this->findTenant($tenantId);
        /** @var list<ImportRow> $orderedRows */
        $orderedRows = [];
        $resolvedCount = 0;

        while ($resolvedCount < count($rows)) {
            $madeProgress = false;

            foreach ($rows as $index => $row) {
                if (array_key_exists('resolved_depth', $row)) {
                    continue;
                }

                if ($row['parent_row_key'] === null) {
                    $rows[$index]['resolved_depth'] = $rootDepth + 1;
                } else {
                    $parentIndex = $rowKeyToIndex[$row['parent_row_key']] ?? null;
                    if ($parentIndex === null || !array_key_exists('resolved_depth', $rows[$parentIndex])) {
                        continue;
                    }

                    $rows[$index]['resolved_depth'] = $rows[$parentIndex]['resolved_depth'] + 1;
                }

                if ($rows[$index]['resolved_depth'] > $tenant['hierarchy_depth_limit']) {
                    $errors[] = new OrganizationHierarchyImportError(
                        $row['row_number'],
                        'parent_row_key',
                        sprintf('Hierarchy depth cannot exceed tenant limit (%d).', $tenant['hierarchy_depth_limit']),
                    );
                }

                $orderedRows[] = $rows[$index];
                $resolvedCount++;
                $madeProgress = true;
            }

            if ($madeProgress) {
                continue;
            }

            foreach ($rows as $row) {
                if (!array_key_exists('resolved_depth', $row)) {
                    $errors[] = new OrganizationHierarchyImportError(
                        $row['row_number'],
                        'parent_row_key',
                        'Rows contain a cycle or unresolved parent dependency.',
                    );
                }
            }

            break;
        }

        return $orderedRows;
    }

    /**
     * @return array{
     *     rows: list<array{index: int, 0: string, 1: string, 2: string, 3: string}>,
     *     row_numbers: array<int, int>,
     *     header_error: OrganizationHierarchyImportError|null
     * }
     */
    private function parseCsvRows(string $csvContent): array
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new RuntimeException('Failed to create temporary stream for CSV parsing.');
        }

        fwrite($stream, $csvContent);
        rewind($stream);

        $header = fgetcsv($stream);
        $headerError = null;
        if ($header === false) {
            $headerError = new OrganizationHierarchyImportError(0, 'csv_file', 'CSV header row is required.');
        } else {
            $normalizedHeader = array_map(
                static fn (string|null $value): string => trim((string) $value),
                $header,
            );
            if ($normalizedHeader !== self::EXPECTED_HEADERS) {
                $headerError = new OrganizationHierarchyImportError(
                    0,
                    'csv_file',
                    sprintf(
                        'CSV header must be exactly: %s.',
                        implode(', ', self::EXPECTED_HEADERS),
                    ),
                );
            }
        }

        $rows = [];
        $rowNumbers = [];
        $lineNumber = 2;
        while (($row = fgetcsv($stream)) !== false) {
            $normalizedRow = array_map(
                static fn (string|null $value): string => trim((string) $value),
                array_pad($row, 4, ''),
            );

            if ($normalizedRow === ['', '', '', '']) {
                $lineNumber++;
                continue;
            }

            $rows[] = [
                'index' => count($rows),
                0 => $normalizedRow[0],
                1 => $normalizedRow[1],
                2 => $normalizedRow[2],
                3 => $normalizedRow[3],
            ];
            $rowNumbers[count($rows) - 1] = $lineNumber;
            $lineNumber++;
        }

        fclose($stream);

        return [
            'rows' => $rows,
            'row_numbers' => $rowNumbers,
            'header_error' => $headerError,
        ];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function findRootNode(int $tenantId): array
    {
        /** @var list<object{id: int, depth: int}> $rows */
        $rows = DB::select(
            'SELECT id, depth
             FROM org_nodes
             WHERE tenant_id = ?
               AND parent_id IS NULL
               AND node_type = ?
             ORDER BY id ASC',
            [$tenantId, OrgNodeType::Company->value],
        );

        if (count($rows) !== 1) {
            throw ValidationException::withMessages([
                'csv_file' => 'Tenant must have exactly one company root node before import.',
            ]);
        }

        return [(int) $rows[0]->id, (int) $rows[0]->depth];
    }

    /**
     * @return array{id: int, hierarchy_depth_limit: int}
     */
    private function findTenant(int $tenantId): array
    {
        /** @var object{id: int, hierarchy_depth_limit: int}|null $row */
        $row = DB::selectOne(
            'SELECT id, hierarchy_depth_limit
             FROM tenants
             WHERE id = ?
             LIMIT 1',
            [$tenantId],
        );

        if ($row === null) {
            throw new RuntimeException('Tenant was not found for current context.');
        }

        return [
            'id' => (int) $row->id,
            'hierarchy_depth_limit' => (int) $row->hierarchy_depth_limit,
        ];
    }

    /**
     * @param  list<array{
     *     row_key: string,
     *     tenant_id: int,
     *     parent_id: int,
     *     node_type: string,
     *     name: string,
     *     depth: int,
     *     is_active: bool,
     *     created_at: string,
     *     updated_at: string
     * }>  $batch
     * @return list<array{
     *     row_key: string,
     *     parent_id: int,
     *     depth: int,
     *     created_at: string,
     *     node: ProvisionedOrgNode
     * }>
     */
    private function insertNodeBatch(array $batch): array
    {
        if ($batch === []) {
            return [];
        }

        $valueSql = [];
        $bindings = [];
        $timestamps = [];

        foreach ($batch as $row) {
            $valueSql[] = '(?, ?, ?, ?, ?, ?, ?, ?)';
            $bindings[] = $row['tenant_id'];
            $bindings[] = $row['parent_id'];
            $bindings[] = $row['node_type'];
            $bindings[] = $row['name'];
            $bindings[] = $row['depth'];
            $bindings[] = $row['is_active'];
            $bindings[] = $row['created_at'];
            $bindings[] = $row['updated_at'];
            $timestamps[] = $row['created_at'];
        }

        DB::insert(
            sprintf(
                'INSERT INTO org_nodes
                    (tenant_id, parent_id, node_type, name, depth, is_active, created_at, updated_at)
                 VALUES %s',
                implode(', ', $valueSql),
            ),
            $bindings,
        );

        $selectBindings = [$batch[0]['tenant_id'], ...$timestamps];
        $placeholderSql = implode(', ', array_fill(0, count($timestamps), '?'));

        /** @var list<object{
         *     id: int,
         *     tenant_id: int,
         *     parent_id: int|null,
         *     node_type: string,
         *     name: string,
         *     depth: int,
         *     is_active: int,
         *     created_at: string
         * }> $rows
         */
        $rows = DB::select(
            sprintf(
                'SELECT id, tenant_id, parent_id, node_type, name, depth, is_active, created_at
                 FROM org_nodes
                 WHERE tenant_id = ?
                   AND created_at IN (%s)
                 ORDER BY created_at ASC',
                $placeholderSql,
            ),
            $selectBindings,
        );

        /** @var array<string, object{
         *     id: int,
         *     tenant_id: int,
         *     parent_id: int|null,
         *     node_type: string,
         *     name: string,
         *     depth: int,
         *     is_active: int,
         *     created_at: string
         * }> $rowsByTimestamp */
        $rowsByTimestamp = [];
        foreach ($rows as $row) {
            $rowsByTimestamp[(string) $row->created_at] = $row;
        }

        $insertedNodes = [];
        foreach ($batch as $row) {
            $insertedRow = $rowsByTimestamp[$row['created_at']] ?? null;
            if ($insertedRow === null) {
                throw new RuntimeException('Failed to resolve inserted org node row after bulk insert.');
            }

            $insertedNodes[] = [
                'row_key' => $row['row_key'],
                'parent_id' => $row['parent_id'],
                'depth' => $row['depth'],
                'created_at' => $row['created_at'],
                'node' => new ProvisionedOrgNode(
                    id: (int) $insertedRow->id,
                    tenantId: (int) $insertedRow->tenant_id,
                    parentId: $insertedRow->parent_id !== null ? (int) $insertedRow->parent_id : null,
                    nodeType: OrgNodeType::from((string) $insertedRow->node_type),
                    name: (string) $insertedRow->name,
                    depth: (int) $insertedRow->depth,
                    isActive: (bool) $insertedRow->is_active,
                ),
            ];
        }

        return $insertedNodes;
    }

    /**
     * @param  list<array{
     *     row_key: string,
     *     parent_id: int,
     *     depth: int,
     *     created_at: string,
     *     node: ProvisionedOrgNode
     * }>  $insertedNodes
     */
    private function insertAuditBatch(int $tenantId, int $actorUserId, array $insertedNodes): void
    {
        if ($insertedNodes === []) {
            return;
        }

        $valueSql = [];
        $bindings = [];

        foreach ($insertedNodes as $row) {
            $valueSql[] = '(?, ?, ?, ?, ?, ?, ?, ?)';
            $bindings[] = $tenantId;
            $bindings[] = $actorUserId;
            $bindings[] = 'org_node_created';
            $bindings[] = 'org_node';
            $bindings[] = $row['node']->id;
            $bindings[] = json_encode([
                'parent_id' => $row['parent_id'],
                'depth' => $row['depth'],
            ], JSON_THROW_ON_ERROR);
            $bindings[] = $row['created_at'];
            $bindings[] = $row['created_at'];
        }

        DB::insert(
            sprintf(
                'INSERT INTO tenant_audit_logs
                    (tenant_id, actor_user_id, action, auditable_type, auditable_id, metadata, created_at, updated_at)
                 VALUES %s',
                implode(', ', $valueSql),
            ),
            $bindings,
        );
    }
}
