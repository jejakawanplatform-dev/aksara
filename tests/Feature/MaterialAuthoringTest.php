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

use App\Enums\MaterialStatus;
use App\Models\LearningMaterial;
use App\Models\LearningPlan;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialAuthoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
        $this->seed(SystemSettingSeeder::class);
    }

    public function test_guru_dapat_mengakses_halaman_edit_bahan_ajar(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();
        $material = LearningMaterial::where('plan_id', $plan->id)->firstOrFail();

        $this->actingAs($guru)->get("/materials/{$material->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Materials/Edit')
                ->where('material.id', $material->id)
                ->where('material.plan.topic', $plan->topic)
                ->has('endpoints.update')
                ->has('endpoints.images')
                ->has('endpoints.copilot')
                ->has('form.sections')
            );
    }

    public function test_guru_dapat_mengakses_index_dan_show_materi_inertia(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();
        $material = LearningMaterial::where('plan_id', $plan->id)->firstOrFail();

        $this->actingAs($guru)->get('/materials')->assertOk()->assertInertia(
            fn ($page) => $page->component('Materials/Index')->has('materials')
        );

        $this->actingAs($guru)->get("/materials/{$material->id}")->assertOk()->assertInertia(
            fn ($page) => $page->component('Materials/Show')->where('material.id', $material->id)
        );
    }

    public function test_guru_dapat_menggunakan_copilot_untuk_menyusun_materi(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();
        $material = LearningMaterial::where('plan_id', $plan->id)->firstOrFail();

        $response = $this->actingAs($guru)->postJson(route('materials.copilot', $material), [
            'message' => 'susun ulang semua materi dengan bahasa sederhana',
            'history' => [],
            'templates' => ['references' => true],
            'title' => 'Judul',
            'sections' => [
                ['heading' => '1. A', 'body' => '<p>isi</p>'],
            ],
            'reflectionsText' => '',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent', 'rewrite');
        $this->assertNotEmpty($response->json('replyMessage'));
    }

    public function test_guru_dapat_menyimpan_draf_dan_menerbitkan_bahan_ajar(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();
        $material = LearningMaterial::where('plan_id', $plan->id)->firstOrFail();

        $payload = [
            'title' => 'Judul Baru Teks Bahan Ajar Siswa',
            'sections' => [
                [
                    'heading' => '1. Pendahuluan Baru',
                    'body' => '<p>Ini paragraf materi siswa baru.</p>',
                ],
            ],
            'reflectionsText' => "Pertanyaan 1\nPertanyaan 2",
        ];

        $this->actingAs($guru)
            ->post(route('materials.publish', $material), $payload)
            ->assertRedirect(route('materials.show', $material));

        $this->assertDatabaseHas('learning_materials', [
            'id' => $material->id,
            'status' => MaterialStatus::Published,
        ]);
    }
}
