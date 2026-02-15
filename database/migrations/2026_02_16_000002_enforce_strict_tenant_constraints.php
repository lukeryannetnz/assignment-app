<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('tenant_audit_logs');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('curriculum_items');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('course_user');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('org_nodes');
        Schema::dropIfExists('users');

        Schema::enableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->boolean('is_student')->default(true);
            $table->boolean('is_admin')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
        });

        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description');
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
        });

        Schema::create('org_nodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->enum('node_type', ['company', 'business_unit', 'department', 'team']);
            $table->string('name');
            $table->unsignedTinyInteger('depth')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
            $table->index(['tenant_id', 'parent_id']);

            $table->foreign(['tenant_id', 'parent_id'])
                ->references(['tenant_id', 'id'])
                ->on('org_nodes')
                ->nullOnDelete();
        });

        Schema::create('course_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'course_id']);

            $table->foreign(['tenant_id', 'user_id'])
                ->references(['tenant_id', 'id'])
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'course_id'])
                ->references(['tenant_id', 'id'])
                ->on('courses')
                ->cascadeOnDelete();
        });

        Schema::create('sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);

            $table->foreign(['tenant_id', 'course_id'])
                ->references(['tenant_id', 'id'])
                ->on('courses')
                ->cascadeOnDelete();
        });

        Schema::create('curriculum_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('section_id');
            $table->enum('type', ['video', 'assignment', 'quiz']);
            $table->string('title');
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->unsignedInteger('order')->default(0);
            $table->text('video_path')->nullable();
            $table->text('assignment_content')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);

            $table->foreign(['tenant_id', 'section_id'])
                ->references(['tenant_id', 'id'])
                ->on('sections')
                ->cascadeOnDelete();
        });

        Schema::create('quiz_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('curriculum_item_id');
            $table->text('question');
            $table->json('options');
            $table->json('correct_answers');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->foreign(['tenant_id', 'curriculum_item_id'])
                ->references(['tenant_id', 'id'])
                ->on('curriculum_items')
                ->cascadeOnDelete();
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('tenant_audit_logs');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('curriculum_items');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('course_user');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('org_nodes');
        Schema::dropIfExists('users');

        Schema::enableForeignKeyConstraints();
    }
};
