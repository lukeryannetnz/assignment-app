<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('role_family')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'id'], 'skill_roles_tenant_id_unique');
            $table->unique(['tenant_id', 'name'], 'skill_roles_tenant_name_unique');
        });

        Schema::create('skill_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('skill_family')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'id'], 'skill_defs_tenant_id_unique');
            $table->unique(['tenant_id', 'name'], 'skill_defs_tenant_name_unique');
        });

        Schema::create('role_skill_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('role_id');
            $table->text('draft_summary')->nullable();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'id'], 'rsm_tenant_id_unique');
            $table->unique(['tenant_id', 'role_id'], 'rsm_tenant_role_unique');

            $table->foreign(['tenant_id', 'role_id'], 'rsm_role_fk')
                ->references(['tenant_id', 'id'])
                ->on('skill_roles')
                ->cascadeOnDelete();
        });

        Schema::create('role_skill_mapping_skills', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('role_skill_mapping_id');
            $table->unsignedBigInteger('skill_id');
            $table->string('importance');
            $table->string('target_proficiency');
            $table->text('rationale_note')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['tenant_id', 'role_skill_mapping_id', 'skill_id'], 'role_mapping_draft_skill_unique');

            $table->foreign(['tenant_id', 'role_skill_mapping_id'], 'rsms_mapping_fk')
                ->references(['tenant_id', 'id'])
                ->on('role_skill_mappings')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'skill_id'], 'rsms_skill_fk')
                ->references(['tenant_id', 'id'])
                ->on('skill_definitions')
                ->cascadeOnDelete();
        });

        Schema::create('role_skill_mapping_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('role_skill_mapping_id');
            $table->unsignedInteger('version_number');
            $table->text('summary')->nullable();
            $table->timestamp('published_at');
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'id'], 'rsmv_tenant_id_unique');
            $table->unique(['tenant_id', 'role_id', 'version_number'], 'rsmv_tenant_role_version_unique');

            $table->foreign(['tenant_id', 'role_id'], 'rsmv_role_fk')
                ->references(['tenant_id', 'id'])
                ->on('skill_roles')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'role_skill_mapping_id'], 'rsmv_mapping_fk')
                ->references(['tenant_id', 'id'])
                ->on('role_skill_mappings')
                ->cascadeOnDelete();
        });

        Schema::create('role_skill_mapping_version_skills', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('role_skill_mapping_version_id');
            $table->unsignedBigInteger('skill_id');
            $table->string('importance');
            $table->string('target_proficiency');
            $table->text('rationale_note')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'role_skill_mapping_version_id', 'skill_id'],
                'role_mapping_version_skill_unique',
            );

            $table->foreign(['tenant_id', 'role_skill_mapping_version_id'], 'rsmvs_version_fk')
                ->references(['tenant_id', 'id'])
                ->on('role_skill_mapping_versions')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'skill_id'], 'rsmvs_skill_fk')
                ->references(['tenant_id', 'id'])
                ->on('skill_definitions')
                ->cascadeOnDelete();
        });

        Schema::table('role_skill_mappings', function (Blueprint $table): void {
            $table->foreign('current_version_id', 'rsm_current_version_fk')
                ->references('id')
                ->on('role_skill_mapping_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('role_skill_mappings', function (Blueprint $table): void {
            $table->dropForeign('rsm_current_version_fk');
        });

        Schema::dropIfExists('role_skill_mapping_version_skills');
        Schema::dropIfExists('role_skill_mapping_versions');
        Schema::dropIfExists('role_skill_mapping_skills');
        Schema::dropIfExists('role_skill_mappings');
        Schema::dropIfExists('skill_definitions');
        Schema::dropIfExists('skill_roles');
    }
};
