<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->string('plan_tier')->default('enterprise_pilot');
            $table->unsignedTinyInteger('hierarchy_depth_limit')->default(4);
            $table->timestamps();
        });

        Schema::create('org_nodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('org_nodes')->nullOnDelete();
            $table->enum('node_type', ['company', 'business_unit', 'department', 'team']);
            $table->string('name');
            $table->unsignedTinyInteger('depth')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'parent_id']);
        });

        Schema::create('tenant_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'action']);
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index('tenant_id');
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index('tenant_id');
        });

        Schema::table('sections', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index('tenant_id');
        });

        Schema::table('curriculum_items', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index('tenant_id');
        });

        Schema::table('quiz_questions', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index('tenant_id');
        });

        Schema::table('course_user', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index(['tenant_id', 'user_id', 'course_id']);
        });

        $tenantId = (int) DB::table('tenants')->insertGetId([
            'name' => 'Legacy Tenant',
            'status' => 'active',
            'plan_tier' => 'enterprise_pilot',
            'hierarchy_depth_limit' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->update(['tenant_id' => $tenantId]);
        DB::table('courses')->update(['tenant_id' => $tenantId]);

        DB::statement(
            'UPDATE sections SET tenant_id = (SELECT tenant_id FROM courses WHERE courses.id = sections.course_id)',
        );
        DB::statement(
            'UPDATE curriculum_items
             SET tenant_id = (
                SELECT tenant_id
                FROM sections
                WHERE sections.id = curriculum_items.section_id
             )',
        );
        DB::statement(
            'UPDATE quiz_questions
             SET tenant_id = (
                SELECT tenant_id
                FROM curriculum_items
                WHERE curriculum_items.id = quiz_questions.curriculum_item_id
             )',
        );
        DB::statement(
            'UPDATE course_user SET tenant_id = (SELECT tenant_id FROM users WHERE users.id = course_user.user_id)',
        );

        DB::table('org_nodes')->insert([
            'tenant_id' => $tenantId,
            'parent_id' => null,
            'node_type' => 'company',
            'name' => 'Legacy Tenant Root',
            'depth' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_user', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'user_id', 'course_id']);
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('quiz_questions', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id']);
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('curriculum_items', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id']);
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('sections', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id']);
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id']);
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id']);
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::dropIfExists('tenant_audit_logs');
        Schema::dropIfExists('org_nodes');
        Schema::dropIfExists('tenants');
    }
};
