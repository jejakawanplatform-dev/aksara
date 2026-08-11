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

use App\Enums\PlanStatus;
use App\Models\LearningPlan;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
        $this->seed(SystemSettingSeeder::class);
    }

    public function test_sidebar_guru_menampilkan_menu_bernomor_urut_5_tahap(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $this->actingAs($guru)->get('/plans')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Plans/Index')
                ->has('auth.permissions')
                ->where('nav.1.title', 'PEMBELAJARAN')
                ->where('nav.1.items.0.label', '1. Rencana Pembelajaran')
                ->where('nav.1.items.1.label', '2. Materi Pembelajaran')
                ->where('nav.1.items.2.label', '3. Rekap Kehadiran')
                ->where('nav.2.items.0.label', '4. Laporan Guru')
                ->where('nav.2.items.1.label', '5. Evaluasi & Refleksi')
            );
    }

    public function test_halaman_index_rencana_inertia(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $this->actingAs($guru)->get('/plans')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Plans/Index')
                ->has('plans.data')
                ->has('createAiUrl')
            );
    }

    public function test_alur_persetujuan_rencana_dan_publikasi_materi(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->first();
        $plan->update(['status' => PlanStatus::Draft]);

        $plan->aiGenerations()->create([
            'created_by' => $guru->id,
            'vendor_id' => 'groq',
            'model' => 'llama-3.3-70b-versatile',
            'input_summary' => ['topic' => 'Test Topic'],
            'output' => [
                'cpDraft' => 'CP Test',
                'tpDraft' => ['TP Test'],
                'atpDraft' => [['sequence' => 1, 'activity' => 'Intro']],
                'learningMaterialDraft' => ['title' => 'Materi Test', 'sections' => []],
            ],
        ]);

        $this->actingAs($guru)
            ->post(route('plans.draft.approve', $plan))
            ->assertRedirect();

        $this->assertDatabaseHas('learning_materials', [
            'plan_id' => $plan->id,
        ]);
        $this->assertSame(PlanStatus::Reviewed, $plan->fresh()->status);

        $this->actingAs($guru)
            ->post(route('plans.draft.publish', $plan))
            ->assertRedirect();

        $this->assertDatabaseHas('learning_plans', [
            'id' => $plan->id,
            'status' => 'published',
        ]);
    }
}
