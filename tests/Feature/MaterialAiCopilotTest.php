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
use App\Models\LearningMaterial;
use App\Models\LearningPlan;
use App\Models\User;
use App\Support\MaterialContentHtml;
use App\Support\MaterialCopilotPatch;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaterialAiCopilotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
        $this->seed(SystemSettingSeeder::class);
    }

    public function test_edit_page_expose_copilot_capability_flags(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();
        $material = LearningMaterial::where('plan_id', $plan->id)->firstOrFail();

        AiProvider::query()->update(['is_active' => false, 'api_key' => null]);
        AiProvider::where('vendor_key', 'groq')->update([
            'is_active' => true,
            'api_key' => 'gsk_test_key',
        ]);

        $this->actingAs($guru)->get(route('materials.edit', $material))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Materials/Edit')
                ->where('canGenerateImages', false)
                ->has('modelChoices')
            );
    }

    public function test_ceklis_generate_gambar_tampil_jika_openai_aktif_berkey(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();
        $material = LearningMaterial::where('plan_id', $plan->id)->firstOrFail();

        AiProvider::where('vendor_key', 'openai')->update([
            'is_active' => true,
            'api_key' => 'sk-test-openai-key',
        ]);

        $this->actingAs($guru)->get(route('materials.edit', $material))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Materials/Edit')
                ->where('canGenerateImages', true)
            );
    }

    public function test_guru_dapat_mengirim_pesan_copilot_json(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();
        $material = LearningMaterial::where('plan_id', $plan->id)->firstOrFail();

        $response = $this->actingAs($guru)->postJson(route('materials.copilot', $material), [
            'message' => 'Sertakan contoh soal cerita dan penjelasan terperinci.',
            'history' => [],
            'templates' => ['case_studies' => true, 'references' => true],
            'title' => 'Judul',
            'sections' => [
                [
                    'heading' => '1. Konsep',
                    'body' => '<p>'.str_repeat('Konten materi yang sudah cukup panjang untuk dianggap bukan placeholder. ', 4).'</p>',
                ],
                [
                    'heading' => '2. Penerapan',
                    'body' => '<p>'.str_repeat('Bagian kedua juga sudah berisi teks substantif yang panjang. ', 4).'</p>',
                ],
            ],
            'reflectionsText' => '',
        ]);

        $response->assertOk();
        $this->assertContains($response->json('intent'), ['patch', 'rewrite', 'create']);
        $this->assertNotEmpty($response->json('replyMessage'));
    }

    public function test_copilot_patch_lalu_simpan_tidak_menghapus_seksi_lain(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();
        $material = LearningMaterial::where('plan_id', $plan->id)->firstOrFail();

        $sections = [
            [
                'heading' => '1. Konsep',
                'body' => '<p>'.str_repeat('Konten materi yang sudah cukup panjang untuk dianggap bukan placeholder. ', 4).'</p>',
            ],
            [
                'heading' => '2. Penerapan',
                'body' => '<p>'.str_repeat('Bagian kedua juga sudah berisi teks substantif yang panjang. ', 4).'</p>',
            ],
        ];

        $copilot = $this->actingAs($guru)->postJson(route('materials.copilot', $material), [
            'message' => 'Perbaiki hanya seksi konsep agar lebih ringkas.',
            'history' => [],
            'templates' => ['references' => true],
            'title' => 'Judul Materi',
            'sections' => $sections,
            'reflectionsText' => '',
        ]);

        $copilot->assertOk();
        $copilot->assertJsonPath('applyMode', 'patch');
        $this->assertNotEmpty($copilot->json('materialData.sections'));

        $merged = MaterialCopilotPatch::mergeSections(
            $sections,
            $copilot->json('materialData.sections')
        );

        $this->assertCount(2, $merged);
        $this->assertStringContainsString('Bagian kedua juga sudah berisi', $merged[1]['body']);

        $this->actingAs($guru)
            ->put(route('materials.update', $material), [
                'title' => 'Judul Materi',
                'sections' => $merged,
                'reflectionsText' => '',
            ])
            ->assertRedirect();

        $saved = $material->fresh()->content;
        $this->assertCount(2, $saved['sections']);
        $this->assertStringContainsString('Bagian kedua juga sudah berisi', $saved['sections'][1]['body']);
    }

    public function test_sanitize_menukar_img_palsu_jadi_blok_ilustrasi(): void
    {
        $sections = MaterialContentHtml::sanitizeSections([
            [
                'heading' => '1. Pengenalan',
                'body' => '<p>Teks</p><img src="https://fake.cdn/ilustrasi.png" alt="Ilustrasi Konten Digital">',
            ],
        ]);

        $this->assertStringNotContainsString('<img', $sections[0]['body']);
        $this->assertStringContainsString('Teks', $sections[0]['body']);
        $this->assertStringNotContainsString('Saran Ilustrasi', $sections[0]['body']);
    }

    public function test_guru_dapat_menyimpan_gambar_materi_ke_storage_public(): void
    {
        Storage::fake('public');

        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();
        $material = LearningMaterial::where('plan_id', $plan->id)->firstOrFail();

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $dataUrl = 'data:image/png;base64,'.base64_encode($png);

        $response = $this->actingAs($guru)->postJson(route('materials.images', $material), [
            'dataUrl' => $dataUrl,
            'originalName' => 'ilustrasi.png',
        ]);

        $response->assertOk();
        $url = $response->json('url');
        $this->assertIsString($url);
        $this->assertStringContainsString('/storage/materials/'.$material->id.'/', $url);

        $relative = ltrim(parse_url($url, PHP_URL_PATH) ?: '', '/');
        $relative = preg_replace('#^storage/#', '', $relative);
        Storage::disk('public')->assertExists($relative);
    }
}
