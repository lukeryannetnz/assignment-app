<?php

declare(strict_types=1);

namespace Tests\Domains\Tenancy\Feature;

use App\Domains\IdentityAccess\Models\User;
use App\Domains\Tenancy\Data\OrgNodeType;
use App\Domains\Tenancy\Events\OrgNodeCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Domains\Foundation\TestCase;

class OrganizationHierarchyImportComponentTest extends TestCase
{
    use RefreshDatabase;

    public function testAdminCanDownloadSampleImportCsvTemplate(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Corp', 4);
        $admin = $this->createUserRecord($tenantId, true, 'sample-import-admin@example.test');
        $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Acme Corp', 0, true);

        $response = $this->actingAs($admin)->get('/admin/tenancy/org-nodes/imports/sample');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader(
            'content-disposition',
            'attachment; filename="org-hierarchy-import-sample.csv"',
        );
        $response->assertSee('row_key,parent_row_key,node_type,name', false);
        $response->assertSee('north-america,,business_unit,North America', false);
        $response->assertSee('engineering,north-america,department,Engineering', false);
        $response->assertSee('platform,engineering,team,Platform Team', false);
    }

    public function testDryRunReturnsResolvedHierarchyPreviewWithoutPersistingNodes(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Corp', 4);
        $admin = $this->createUserRecord($tenantId, true, 'import-admin@example.test');
        $rootNodeId = $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Acme Corp', 0, true);

        $response = $this->actingAs($admin)->post('/admin/tenancy/org-nodes/imports/dry-run', [
            'csv_file' => UploadedFile::fake()->createWithContent('org.csv', implode("\n", [
                'row_key,parent_row_key,node_type,name',
                'north-america,,business_unit,North America',
                'engineering,north-america,department,Engineering',
                'platform,engineering,team,Platform Team',
            ])),
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.can_commit', true);
        $response->assertJsonPath('data.rows.0.row_key', 'north-america');
        $response->assertJsonPath('data.rows.0.resolved_depth', 1);
        $response->assertJsonPath('data.rows.1.resolved_depth', 2);
        $response->assertJsonPath('data.rows.2.resolved_depth', 3);
        $response->assertJsonPath('data.errors', []);

        /** @var object{node_count: int} $nodeCount */
        $nodeCount = $this->selectOne(
            'SELECT COUNT(*) AS node_count
             FROM org_nodes
             WHERE tenant_id = ?',
            [$tenantId],
        );
        $this->assertSame(1, (int) $nodeCount->node_count);

        /** @var object{id: int} $root */
        $root = $this->selectOne(
            'SELECT id
             FROM org_nodes
             WHERE tenant_id = ? AND parent_id IS NULL
             LIMIT 1',
            [$tenantId],
        );
        $this->assertSame($rootNodeId, (int) $root->id);
    }

    public function testCommitImportsHierarchyTransactionallyAndWritesAuditRows(): void
    {
        Event::fake();

        $tenantId = $this->insertTenantRecord('Acme Corp', 4);
        $admin = $this->createUserRecord($tenantId, true, 'commit-admin@example.test');
        $rootNodeId = $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Acme Corp', 0, true);

        $response = $this->actingAs($admin)->postJson('/admin/tenancy/org-nodes/imports', [
            'csv_file' => UploadedFile::fake()->createWithContent('org.csv', implode("\n", [
                'row_key,parent_row_key,node_type,name',
                'north-america,,business_unit,North America',
                'engineering,north-america,department,Engineering',
                'platform,engineering,team,Platform Team',
            ])),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.imported_count', 3);
        $response->assertJsonPath('data.created_nodes.0.parent_id', $rootNodeId);
        $response->assertJsonPath('data.created_nodes.0.node_type', OrgNodeType::BusinessUnit->value);
        $response->assertJsonPath('data.created_nodes.1.node_type', OrgNodeType::Department->value);
        $response->assertJsonPath('data.created_nodes.2.node_type', OrgNodeType::Team->value);

        /** @var list<object{id: int, parent_id: int|null, node_type: string, name: string, depth: int}> $rows */
        $rows = DB::select(
            'SELECT id, parent_id, node_type, name, depth
             FROM org_nodes
             WHERE tenant_id = ?
             ORDER BY depth ASC, id ASC',
            [$tenantId],
        );
        $this->assertCount(4, $rows);
        $this->assertSame('Acme Corp', $rows[0]->name);
        $this->assertSame($rootNodeId, (int) $rows[1]->parent_id);
        $this->assertSame('North America', $rows[1]->name);
        $this->assertSame(OrgNodeType::BusinessUnit->value, $rows[1]->node_type);
        $this->assertSame((int) $rows[1]->id, (int) $rows[2]->parent_id);
        $this->assertSame(OrgNodeType::Department->value, $rows[2]->node_type);
        $this->assertSame((int) $rows[2]->id, (int) $rows[3]->parent_id);
        $this->assertSame(OrgNodeType::Team->value, $rows[3]->node_type);

        /** @var object{audit_count: int} $auditCount */
        $auditCount = $this->selectOne(
            'SELECT COUNT(*) AS audit_count
             FROM tenant_audit_logs
             WHERE tenant_id = ?
               AND action = ?',
            [$tenantId, 'org_node_created'],
        );
        $this->assertSame(3, (int) $auditCount->audit_count);

        Event::assertDispatchedTimes(OrgNodeCreated::class, 3);
    }

    public function testCommitImportsRealisticLargeHierarchyWithDozensOfTeams(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Corp', 4);
        $admin = $this->createUserRecord($tenantId, true, 'large-import-admin@example.test');
        $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Acme Corp', 0, true);

        $csvRows = ['row_key,parent_row_key,node_type,name'];
        $expectedImportedCount = 0;

        for ($businessUnit = 1; $businessUnit <= 4; $businessUnit++) {
            $businessUnitKey = sprintf('bu-%d', $businessUnit);
            $csvRows[] = sprintf(
                '%s,,business_unit,Business Unit %d',
                $businessUnitKey,
                $businessUnit,
            );
            $expectedImportedCount++;

            for ($department = 1; $department <= 3; $department++) {
                $departmentKey = sprintf('%s-dept-%d', $businessUnitKey, $department);
                $csvRows[] = sprintf(
                    '%s,%s,department,Department %d.%d',
                    $departmentKey,
                    $businessUnitKey,
                    $businessUnit,
                    $department,
                );
                $expectedImportedCount++;

                for ($team = 1; $team <= 4; $team++) {
                    $teamKey = sprintf('%s-team-%d', $departmentKey, $team);
                    $csvRows[] = sprintf(
                        '%s,%s,team,Team %d.%d.%d',
                        $teamKey,
                        $departmentKey,
                        $businessUnit,
                        $department,
                        $team,
                    );
                    $expectedImportedCount++;
                }
            }
        }

        $response = $this->actingAs($admin)->postJson('/admin/tenancy/org-nodes/imports', [
            'csv_file' => UploadedFile::fake()->createWithContent('large-org.csv', implode("\n", $csvRows)),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.imported_count', $expectedImportedCount);

        /** @var object{node_count: int} $nodeCount */
        $nodeCount = $this->selectOne(
            'SELECT COUNT(*) AS node_count
             FROM org_nodes
             WHERE tenant_id = ?',
            [$tenantId],
        );
        $this->assertSame($expectedImportedCount + 1, (int) $nodeCount->node_count);

        /** @var object{team_count: int} $teamCount */
        $teamCount = $this->selectOne(
            'SELECT COUNT(*) AS team_count
             FROM org_nodes
             WHERE tenant_id = ?
               AND node_type = ?',
            [$tenantId, OrgNodeType::Team->value],
        );
        $this->assertSame(48, (int) $teamCount->team_count);
    }

    public function testDryRunReturnsBlockingErrorsForInvalidHierarchyRows(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Corp', 2);
        $admin = $this->createUserRecord($tenantId, true, 'invalid-import-admin@example.test');
        $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Acme Corp', 0, true);

        $response = $this->actingAs($admin)->post('/admin/tenancy/org-nodes/imports/dry-run', [
            'csv_file' => UploadedFile::fake()->createWithContent('org.csv', implode("\n", [
                'row_key,parent_row_key,node_type,name',
                'root,,company,Acme Corp',
                'engineering,missing-parent,department,Engineering',
                'platform,engineering,team,Platform Team',
            ])),
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.can_commit', false);
        $response->assertJsonPath('data.errors.0.field', 'node_type');
        $response->assertJsonPath('data.errors.1.field', 'parent_row_key');
    }

    public function testCommitRejectsInvalidImportAndLeavesDatabaseUnchanged(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Corp', 2);
        $admin = $this->createUserRecord($tenantId, true, 'reject-import-admin@example.test');
        $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Acme Corp', 0, true);

        $response = $this->actingAs($admin)->post(
            '/admin/tenancy/org-nodes/imports',
            [
                'csv_file' => UploadedFile::fake()->createWithContent('org.csv', implode("\n", [
                    'row_key,parent_row_key,node_type,name',
                    'north-america,,business_unit,North America',
                    'engineering,north-america,department,Engineering',
                    'platform,engineering,team,Platform Team',
                ])),
            ],
            ['Accept' => 'application/json'],
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['csv_file']);

        /** @var object{node_count: int} $nodeCount */
        $nodeCount = $this->selectOne(
            'SELECT COUNT(*) AS node_count
             FROM org_nodes
             WHERE tenant_id = ?',
            [$tenantId],
        );
        $this->assertSame(1, (int) $nodeCount->node_count);
    }

    public function testHtmlWorkflowSupportsDryRunReviewAndCommitWithoutReuploadingFile(): void
    {
        $tenantId = $this->insertTenantRecord('Acme Corp', 4);
        $admin = $this->createUserRecord($tenantId, true, 'html-import-admin@example.test');
        $this->insertOrgNodeRecord($tenantId, null, OrgNodeType::Company, 'Acme Corp', 0, true);

        $dryRunResponse = $this->actingAs($admin)->post('/admin/tenancy/org-nodes/imports/dry-run', [
            'ui_form' => '1',
            'csv_file' => UploadedFile::fake()->createWithContent('org.csv', implode("\n", [
                'row_key,parent_row_key,node_type,name',
                'north-america,,business_unit,North America',
                'engineering,north-america,department,Engineering',
                'platform,engineering,team,Platform Team',
            ])),
        ]);

        $dryRunResponse->assertRedirect('/admin/tenancy/org-nodes');
        $dryRunResponse->assertSessionHas('tenancy.org_import.preview');

        $reviewResponse = $this->actingAs($admin)->get('/admin/tenancy/org-nodes');

        $reviewResponse->assertOk();
        $reviewResponse->assertSee('Dry-Run Results');
        $reviewResponse->assertSee('North America');
        $reviewResponse->assertSee('Ready');

        $commitResponse = $this->actingAs($admin)->post('/admin/tenancy/org-nodes/imports', [
            'ui_form' => '1',
        ]);

        $commitResponse->assertRedirect('/admin/tenancy/org-nodes');
        $commitResponse->assertSessionHas('status', 'CSV import committed successfully. 3 nodes created.');

        $followUpResponse = $this->actingAs($admin)->get('/admin/tenancy/org-nodes');

        $followUpResponse->assertOk();
        $followUpResponse->assertSee('Import Completed');
        $followUpResponse->assertSee('3 nodes were created from the approved CSV review.');

        /** @var object{node_count: int} $nodeCount */
        $nodeCount = $this->selectOne(
            'SELECT COUNT(*) AS node_count
             FROM org_nodes
             WHERE tenant_id = ?',
            [$tenantId],
        );
        $this->assertSame(4, (int) $nodeCount->node_count);
    }

    private function insertTenantRecord(string $name, int $hierarchyDepthLimit): int
    {
        DB::insert(
            'INSERT INTO tenants (name, status, plan_tier, hierarchy_depth_limit, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$name, 'active', 'enterprise_pilot', $hierarchyDepthLimit, now(), now()],
        );

        return (int) DB::getPdo()->lastInsertId();
    }

    private function insertOrgNodeRecord(
        int $tenantId,
        ?int $parentId,
        OrgNodeType $nodeType,
        string $name,
        int $depth,
        bool $isActive,
    ): int {
        DB::insert(
            'INSERT INTO org_nodes (tenant_id, parent_id, node_type, name, depth, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$tenantId, $parentId, $nodeType->value, $name, $depth, $isActive, now(), now()],
        );

        return (int) DB::getPdo()->lastInsertId();
    }

    private function createUserRecord(int $tenantId, bool $isAdmin, string $email): User
    {
        DB::insert(
            'INSERT INTO users
                (tenant_id, name, email, email_verified_at, password, remember_token,
                 is_student, is_admin, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $tenantId,
                $isAdmin ? 'Admin User' : 'Member User',
                $email,
                now(),
                bcrypt('password'),
                substr(md5($email), 0, 10),
                true,
                $isAdmin,
                now(),
                now(),
            ],
        );

        $user = new User();
        $user->forceFill([
            'id' => (int) DB::getPdo()->lastInsertId(),
            'tenant_id' => $tenantId,
            'name' => $isAdmin ? 'Admin User' : 'Member User',
            'email' => $email,
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'remember_token' => substr(md5($email), 0, 10),
            'is_student' => true,
            'is_admin' => $isAdmin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user->exists = true;

        return $user;
    }

    /**
     * @param  array<int, mixed>  $bindings
     */
    private function selectOne(string $sql, array $bindings): object
    {
        $row = DB::selectOne($sql, $bindings);
        $this->assertNotNull($row);
        $this->assertIsObject($row);

        return $row;
    }
}
