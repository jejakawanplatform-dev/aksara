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

use App\Models\AiProvider;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
        $this->seed(SystemSettingSeeder::class);
    }

    public function test_admin_bisa_mengakses_halaman_pengaturan_sistem(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Settings/Index'));
    }

    public function test_opsi_model_per_fitur_hanya_dari_vendor_aktif(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        AiProvider::query()->update(['is_active' => false]);
        AiProvider::where('vendor_key', 'groq')->update([
            'is_active' => true,
            'api_key' => 'gsk_test',
        ]);

        $this->actingAs($admin)
            ->get(route('settings.index', ['tab' => 'ai']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Index')
                ->has('featureModelOptions')
                ->where('featureModelOptions', fn ($opts) => collect($opts)->contains('llama-3.3-70b-versatile')
                    && ! collect($opts)->contains('gpt-4o')
                    && ! collect($opts)->contains('gemini-1.5-flash')
                )
            );
    }

    public function test_guru_ditolak_mengakses_halaman_pengaturan_sistem(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $this->actingAs($guru)
            ->get(route('settings.index'))
            ->assertStatus(403);
    }

    public function test_admin_dapat_menyimpan_pengaturan_sistem(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('settings.save'), [
                'ai_provider' => 'openai',
                'ai_daily_limit_per_teacher' => 50,
                'ai_anonymize_student_data' => true,
                'ai_model_plan' => 'llama-3.3-70b-versatile',
                'ai_model_material' => 'llama-3.3-70b-versatile',
                'ai_model_improve' => 'llama-3.1-8b-instant',
                'ai_model_quiz' => 'llama-3.3-70b-versatile',
                'security_allow_public_registration' => false,
                'security_session_timeout_minutes' => 60,
                'security_max_login_attempts' => 5,
                'features_quiz_module' => true,
                'features_parent_portal' => true,
                'system_maintenance_mode' => false,
            ])
            ->assertRedirect()
            ->assertSessionHas('message');

        $this->assertEquals('openai', setting('ai.provider'));
        $this->assertEquals(50, setting('ai.daily_limit_per_teacher'));
    }

    public function test_admin_dapat_menyimpan_profil_sekolah_di_referensi(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('references.school'), [
                'name' => 'SMP Aksara Nusantara',
                'npsn' => '12345678',
                'address' => 'Jl. Pendidikan No. 1',
                'headmaster' => 'Drs. H. Mulyadi, M.Pd.',
                'phone' => '021-5551234',
            ])
            ->assertRedirect()
            ->assertSessionHas('message', 'Profil sekolah berhasil disimpan.');

        $this->assertEquals('SMP Aksara Nusantara', setting('school.name'));
    }

    public function test_api_key_disensor_saat_dikirim_ke_frontend(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $provider = AiProvider::where('vendor_key', 'groq')->firstOrFail();
        $provider->update(['api_key' => 'gsk_secret_token_12345']);

        $this->actingAs($admin)
            ->get(route('settings.index', ['tab' => 'ai']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Index')
                ->where('providers', function ($providers) {
                    $groq = collect($providers)->firstWhere('vendor_key', 'groq');

                    return str_starts_with((string) $groq['api_key'], '••••')
                        && ! str_contains((string) $groq['api_key'], 'secret_token')
                        && $groq['has_api_key'] === true;
                })
            );
    }

    public function test_update_provider_tidak_menimpa_kunci_jika_berisi_karakter_sensor(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $provider = AiProvider::where('vendor_key', 'groq')->firstOrFail();
        $provider->update(['api_key' => 'original_secret_token_12345']);

        $this->actingAs($admin)
            ->put(route('settings.providers.update', $provider), [
                'name' => 'Groq Super Fast',
                'is_active' => true,
                'api_key' => '••••••••2345',
                'base_url' => 'https://api.groq.com/openai/v1',
                'model' => 'llama-3.3-70b-versatile',
                'max_tokens' => 4096,
                'temperature' => 0.7,
                'timeout' => 30,
            ])
            ->assertRedirect();

        $provider->refresh();
        $this->assertEquals('Groq Super Fast', $provider->name);
        $this->assertEquals('original_secret_token_12345', $provider->api_key);
    }

    public function test_rate_limiter_aktif_pada_endpoint_test_koneksi(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        for ($i = 0; $i < 15; $i++) {
            $this->actingAs($admin)
                ->postJson(route('settings.providers.test'), [
                    'vendor_key' => 'mock',
                ])
                ->assertOk();
        }

        // Request ke-16 harus ditolak oleh rate limiter (HTTP 429)
        $this->actingAs($admin)
            ->postJson(route('settings.providers.test'), [
                'vendor_key' => 'mock',
            ])
            ->assertStatus(429);
    }
}
