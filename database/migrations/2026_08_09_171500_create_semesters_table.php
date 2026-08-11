<?php

/**
 * Aksara — platform pembelajaran berbantuan AI.
 *
 * @copyright 2026 jejakawan (https://jejakawan.com)
 * @license   MIT
 *
 * Clone, fork, and modification are permitted under the MIT License.
 * See the LICENSE file in the project root.
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('name'); // Ganjil / Genap
            $table->string('code', 20); // ganjil / genap
            $table->unsignedTinyInteger('number'); // 1 = Ganjil, 2 = Genap
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['academic_year_id', 'number']);
            $table->unique(['academic_year_id', 'code']);
        });

        Schema::table('curriculum_atp_items', function (Blueprint $table) {
            $table->foreignId('semester_id')
                ->nullable()
                ->after('academic_year_id')
                ->constrained('semesters')
                ->nullOnDelete();
        });

        Schema::table('learning_plans', function (Blueprint $table) {
            $table->foreignId('semester_id')
                ->nullable()
                ->after('academic_year_id')
                ->constrained('semesters')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('learning_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('semester_id');
        });

        Schema::table('curriculum_atp_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('semester_id');
        });

        Schema::dropIfExists('semesters');
    }
};
