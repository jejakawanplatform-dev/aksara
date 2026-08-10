<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('learning_plans')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('present'); // present|excused|sick|absent
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'student_id']);
            $table->index('plan_id');
        });

        Schema::create('learning_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('learning_materials')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('event_type', 50);              // material_opened|material_read
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['material_id', 'student_id']);
            $table->index('occurred_at');
        });

        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('learning_plans')->cascadeOnDelete();
            $table->string('title');
            $table->json('questions');                     // [{text, options:[...], answer_index}]
            $table->string('status')->default('draft');    // draft|published
            $table->timestamps();
            $table->softDeletes();

            $table->index(['plan_id', 'status']);
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->json('answers');
            $table->unsignedTinyInteger('score');          // 0-100
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->unique(['quiz_id', 'student_id']);
            $table->index('quiz_id');
        });

        Schema::create('teacher_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('learning_plans')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->text('notes');
            $table->text('challenges')->nullable();
            $table->text('next_action')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'teacher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_evaluations');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('learning_events');
        Schema::dropIfExists('attendance_records');
    }
};
