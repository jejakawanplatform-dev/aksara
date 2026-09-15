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
use App\Models\LearningEvent;
use App\Models\LearningMaterial;
use App\Models\LearningPlan;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialBulkActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_guru_bisa_bulk_delete_materi_draf(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::factory()->create(['teacher_id' => $guru->id]);

        $matA = LearningMaterial::factory()->create([
            'plan_id' => $plan->id,
            'status'  => MaterialStatus::Draft,
        ]);
        $matB = LearningMaterial::factory()->create([
            'plan_id' => $plan->id,
            'status'  => MaterialStatus::Draft,
        ]);

        $response = $this->actingAs($guru)
            ->post(route('materials.bulk-destroy'), [
                'ids' => [$matA->id, $matB->id],
            ]);

        $response->assertRedirect(route('materials.index'));
        $response->assertSessionHas('message');

        $this->assertSoftDeleted('learning_materials', ['id' => $matA->id]);
        $this->assertSoftDeleted('learning_materials', ['id' => $matB->id]);
    }

    public function test_materi_published_yang_sudah_dibaca_siswa_dilindungi(): void
    {
        $guru    = User::where('email', 'naya@aksara.test')->firstOrFail();
        $student = User::where('email', 'adit@aksara.test')->firstOrFail();
        $plan    = LearningPlan::factory()->create(['teacher_id' => $guru->id]);

        $material = LearningMaterial::factory()->create([
            'plan_id' => $plan->id,
            'status'  => MaterialStatus::Published,
        ]);

        // Simulasikan siswa sudah membaca materi
        LearningEvent::create([
            'material_id'  => $material->id,
            'student_id'   => $student->id,
            'event_type'   => 'material_opened',
            'occurred_at'  => now(),
        ]);

        $response = $this->actingAs($guru)
            ->post(route('materials.bulk-destroy'), [
                'ids' => [$material->id],
            ]);

        $response->assertRedirect(route('materials.index'));
        $response->assertSessionHas('error');

        // Materi tidak boleh terhapus
        $this->assertDatabaseHas('learning_materials', ['id' => $material->id, 'deleted_at' => null]);
    }

    public function test_materi_published_tanpa_history_baca_bisa_dihapus(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::factory()->create(['teacher_id' => $guru->id]);

        $material = LearningMaterial::factory()->create([
            'plan_id' => $plan->id,
            'status'  => MaterialStatus::Published,
        ]);

        $response = $this->actingAs($guru)
            ->post(route('materials.bulk-destroy'), [
                'ids' => [$material->id],
            ]);

        $response->assertRedirect(route('materials.index'));
        $response->assertSessionHas('message');

        $this->assertSoftDeleted('learning_materials', ['id' => $material->id]);
    }

    public function test_siswa_tidak_bisa_akses_bulk_destroy(): void
    {
        $student = User::where('email', 'adit@aksara.test')->firstOrFail();

        $response = $this->actingAs($student)
            ->post(route('materials.bulk-destroy'), ['ids' => [1]]);

        $response->assertStatus(403);
    }

    public function test_tamu_tidak_bisa_akses_bulk_destroy(): void
    {
        $response = $this->post(route('materials.bulk-destroy'), ['ids' => [1]]);

        $response->assertRedirect('/login');
    }
}
