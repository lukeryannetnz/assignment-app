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
        Schema::create('curriculum_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['video', 'assignment', 'quiz']);
            $table->string('title');
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->unsignedInteger('order')->default(0);
            $table->text('video_path')->nullable();
            $table->text('assignment_content')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_items');
    }
};
