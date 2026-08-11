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

namespace Database\Seeders;

use App\Models\AiProvider;
use App\Support\Ai\AiVendorProviderCatalog;
use Illuminate\Database\Seeder;

class AiProviderSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = AiVendorProviderCatalog::all();
        $order = 1;

        foreach ($vendors as $key => $meta) {
            AiProvider::firstOrCreate(
                ['vendor_key' => $key],
                [
                    'name' => $meta['name'],
                    'is_active' => in_array($key, ['gemini', 'groq', 'mock']),
                    'priority_order' => $order++,
                    'api_key' => $key === 'gemini' ? config('services.ai.key', '') : null,
                    'base_url' => $meta['base_url'],
                    'model' => $meta['default_model'],
                    'max_tokens' => 2048,
                    'temperature' => 0.70,
                    'timeout_seconds' => 30,
                    'is_custom' => false,
                ]
            );
        }
    }
}
