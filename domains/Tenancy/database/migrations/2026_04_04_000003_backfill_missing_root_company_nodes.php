<?php

declare(strict_types=1);

use App\Domains\Tenancy\Data\OrgNodeType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /** @var list<object{id: int, name: string}> $tenants */
        $tenants = DB::select('SELECT id, name FROM tenants ORDER BY id ASC');

        foreach ($tenants as $tenant) {
            $tenantId = (int) $tenant->id;

            /** @var list<object{id: int}> $rootCompanyNodes */
            $rootCompanyNodes = DB::select(
                'SELECT id
                 FROM org_nodes
                 WHERE tenant_id = ?
                   AND parent_id IS NULL
                   AND node_type = ?
                 ORDER BY id ASC',
                [$tenantId, OrgNodeType::Company->value],
            );

            if ($rootCompanyNodes !== []) {
                continue;
            }

            DB::transaction(function () use ($tenant, $tenantId): void {
                $now = now();
                $rootCompanyName = trim((string) $tenant->name);
                if ($rootCompanyName === '') {
                    $rootCompanyName = sprintf('Tenant %d', $tenantId);
                }

                $rootCompanyId = (int) DB::table('org_nodes')->insertGetId([
                    'tenant_id' => $tenantId,
                    'parent_id' => null,
                    'node_type' => OrgNodeType::Company->value,
                    'name' => $rootCompanyName,
                    'depth' => 0,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                /** @var list<object{id: int}> $topLevelNodes */
                $topLevelNodes = DB::select(
                    'SELECT id
                     FROM org_nodes
                     WHERE tenant_id = ?
                       AND parent_id IS NULL
                       AND id <> ?
                     ORDER BY id ASC',
                    [$tenantId, $rootCompanyId],
                );

                foreach ($topLevelNodes as $topLevelNode) {
                    $subtreeNodeIds = $this->subtreeNodeIds($tenantId, (int) $topLevelNode->id);

                    DB::update(
                        'UPDATE org_nodes SET parent_id = ?, depth = ?, updated_at = ? WHERE id = ? AND tenant_id = ?',
                        [$rootCompanyId, 1, $now, (int) $topLevelNode->id, $tenantId],
                    );

                    foreach ($subtreeNodeIds as $nodeId) {
                        if ($nodeId === (int) $topLevelNode->id) {
                            continue;
                        }

                        DB::update(
                            'UPDATE org_nodes SET depth = depth + 1, updated_at = ? WHERE id = ? AND tenant_id = ?',
                            [$now, $nodeId, $tenantId],
                        );
                    }
                }

                DB::insert(
                    'INSERT INTO tenant_audit_logs
                        (tenant_id, actor_user_id, action, auditable_type,
                         auditable_id, metadata, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $tenantId,
                        null,
                        'org_node_created',
                        'org_node',
                        $rootCompanyId,
                        json_encode([
                            'parent_id' => null,
                            'node_type' => OrgNodeType::Company->value,
                            'name' => $rootCompanyName,
                            'depth' => 0,
                            'repair' => 'backfill_missing_root_company',
                        ], JSON_THROW_ON_ERROR),
                        $now,
                        $now,
                    ],
                );

                DB::insert(
                    'INSERT INTO tenant_audit_logs
                        (tenant_id, actor_user_id, action, auditable_type,
                         auditable_id, metadata, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $tenantId,
                        null,
                        'tenant_repaired',
                        'tenant',
                        $tenantId,
                        json_encode([
                            'repair' => 'backfill_missing_root_company',
                            'root_org_node_id' => $rootCompanyId,
                            'reparented_top_level_node_count' => count($topLevelNodes),
                        ], JSON_THROW_ON_ERROR),
                        $now,
                        $now,
                    ],
                );
            });
        }
    }

    public function down(): void
    {
        // Backfill only. Data repair is intentionally not reversed.
    }

    /**
     * @return list<int>
     */
    private function subtreeNodeIds(int $tenantId, int $rootNodeId): array
    {
        $pendingIds = [$rootNodeId];
        $subtreeNodeIds = [];

        while ($pendingIds !== []) {
            $nodeId = array_shift($pendingIds);
            if ($nodeId === null) {
                continue;
            }

            $subtreeNodeIds[] = $nodeId;

            /** @var list<object{id: int}> $children */
            $children = DB::select(
                'SELECT id
                 FROM org_nodes
                 WHERE tenant_id = ?
                   AND parent_id = ?
                 ORDER BY id ASC',
                [$tenantId, $nodeId],
            );

            foreach ($children as $child) {
                $pendingIds[] = (int) $child->id;
            }
        }

        return $subtreeNodeIds;
    }
};
