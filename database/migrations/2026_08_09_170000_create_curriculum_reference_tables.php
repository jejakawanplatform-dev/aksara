<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 2025/2026
            $table->string('code', 20)->unique(); // 2025-2026
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('code', 32)->nullable()->after('name');
            $table->string('phase', 10)->nullable()->after('code'); // D
            $table->string('jenjang', 20)->nullable()->after('phase'); // SMP
            $table->text('description')->nullable()->after('jenjang');
        });

        Schema::table('school_classes', function (Blueprint $table) {
            $table->foreignId('academic_year_id')
                ->nullable()
                ->after('id')
                ->constrained('academic_years')
                ->nullOnDelete();
            $table->string('rombel_code', 32)->nullable()->after('name'); // VII-A
        });

        Schema::create('curriculum_cps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('phase', 10); // D
            $table->string('element_code', 20); // BK, TIK, SK, ...
            $table->string('element_name');
            $table->text('statement');
            $table->string('source_note')->nullable();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->timestamps();

            $table->unique(['subject_id', 'phase', 'element_code']);
        });

        Schema::create('curriculum_tps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_cp_id')->constrained('curriculum_cps')->cascadeOnDelete();
            $table->string('code', 40); // BK-VII-01
            $table->text('statement');
            $table->unsignedTinyInteger('grade')->nullable(); // 7/8/9
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->timestamps();

            $table->unique(['curriculum_cp_id', 'code']);
        });

        Schema::create('curriculum_atp_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('curriculum_tp_id')->constrained('curriculum_tps')->cascadeOnDelete();
            $table->unsignedTinyInteger('grade'); // 7
            $table->unsignedSmallInteger('sequence');
            $table->string('unit_title')->nullable();
            $table->unsignedSmallInteger('estimated_meetings')->nullable();
            $table->timestamps();

            $table->unique(['subject_id', 'grade', 'sequence', 'academic_year_id'], 'atp_unique_seq');
        });

        Schema::table('learning_plans', function (Blueprint $table) {
            $table->foreignId('academic_year_id')
                ->nullable()
                ->after('teacher_id')
                ->constrained('academic_years')
                ->nullOnDelete();
            $table->foreignId('curriculum_cp_id')
                ->nullable()
                ->after('subject_id')
                ->constrained('curriculum_cps')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('learning_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('curriculum_cp_id');
            $table->dropConstrainedForeignId('academic_year_id');
        });

        Schema::dropIfExists('curriculum_atp_items');
        Schema::dropIfExists('curriculum_tps');
        Schema::dropIfExists('curriculum_cps');

        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_year_id');
            $table->dropColumn('rombel_code');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['code', 'phase', 'jenjang', 'description']);
        });

        Schema::dropIfExists('academic_years');
    }
};
