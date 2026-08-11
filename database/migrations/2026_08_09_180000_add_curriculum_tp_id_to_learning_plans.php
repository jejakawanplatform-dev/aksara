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
        Schema::table('learning_plans', function (Blueprint $table) {
            $table->foreignId('curriculum_tp_id')
                ->nullable()
                ->after('curriculum_cp_id')
                ->constrained('curriculum_tps')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('learning_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('curriculum_tp_id');
        });
    }
};
