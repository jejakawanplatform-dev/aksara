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
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_key', 50)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->integer('priority_order')->default(1);
            $table->text('api_key')->nullable();
            $table->string('base_url', 255)->nullable();
            $table->string('model', 100);
            $table->integer('max_tokens')->default(2048);
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->integer('timeout_seconds')->default(30);
            $table->json('custom_headers')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'priority_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
