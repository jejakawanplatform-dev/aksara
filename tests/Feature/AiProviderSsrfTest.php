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

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiProviderSsrfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
        $this->seed(SystemSettingSeeder::class);
    }

    public function test_url_dengan_protokol_http_ke_host_luar_ditolak(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $response = $this->actingAs($admin)->postJson(route('settings.providers.test'), [
            'vendor_key' => 'openai',
            'base_url' => 'http://api.openai.com/v1',
            'api_key' => 'sk-test',
        ]);

        $response->assertOk()
            ->assertJson([
                'type' => 'danger',
                'message' => 'Base URL wajib menggunakan protokol HTTPS yang aman.',
            ]);
    }

    public function test_host_cloud_metadata_ditolak_oleh_proteksi_ssrf(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $response = $this->actingAs($admin)->postJson(route('settings.providers.test'), [
            'vendor_key' => 'openai',
            'base_url' => 'https://169.254.169.254/latest/meta-data',
            'api_key' => 'sk-test',
        ]);

        $response->assertOk()
            ->assertJson([
                'type' => 'danger',
                'message' => 'Host tujuan tidak diizinkan demi keamanan (SSRF Protection).',
            ]);
    }

    public function test_url_dengan_user_info_ditolak(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $response = $this->actingAs($admin)->postJson(route('settings.providers.test'), [
            'vendor_key' => 'openai',
            'base_url' => 'https://admin:secret@api.openai.com/v1',
            'api_key' => 'sk-test',
        ]);

        $response->assertOk()
            ->assertJson([
                'type' => 'danger',
                'message' => 'User-info pada Base URL tidak diizinkan.',
            ]);
    }
}
