<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('phase', 10);                      // D, E, dll
            $table->unsignedTinyInteger('grade');             // 7, 8, 9
            $table->string('topic');
            $table->unsignedSmallInteger('duration_minutes');
            $table->text('learning_objectives');
            $table->text('student_needs')->nullable();
            $table->text('curriculum_reference');
            $table->string('status')->default('draft');       // draft|reviewed|published
            $table->timestamps();
            $table->softDeletes();

            $table->index(['teacher_id', 'status']);
            $table->index(['class_id', 'status']);
        });

        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('learning_plans')->cascadeOnDelete();
            $table->json('content');
            $table->string('status')->default('draft');       // draft|published|archived
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['plan_id', 'status']);
        });

        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('learning_plans')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->json('input_summary');
            $table->json('output')->nullable();
            $table->string('model', 100);
            $table->string('review_status')->default('pending'); // pending|approved|rejected
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('plan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
        Schema::dropIfExists('learning_materials');
        Schema::dropIfExists('learning_plans');
    }
};
