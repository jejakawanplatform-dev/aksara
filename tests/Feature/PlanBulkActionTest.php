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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanBulkActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_guru_bisa_bulk_delete_rpp_draf_miliknya(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        // Buat 2 RPP draf milik guru ini
        $planA = LearningPlan::factory()->create([
            'teacher_id' => $guru->id,
            'topic'      => 'Topik Dummy A',
            'status'     => PlanStatus::Draft,
        ]);
        $planB = LearningPlan::factory()->create([
            'teacher_id' => $guru->id,
            'topic'      => 'Topik Dummy B',
            'status'     => PlanStatus::Draft,
        ]);

        $response = $this->actingAs($guru)
            ->post(route('plans.bulk-destroy'), [
                'ids' => [$planA->id, $planB->id],
            ]);

        $response->assertRedirect(route('plans.index'));
        $response->assertSessionHas('message');

        $this->assertSoftDeleted('learning_plans', ['id' => $planA->id]);
        $this->assertSoftDeleted('learning_plans', ['id' => $planB->id]);
    }

    public function test_guru_tidak_bisa_bulk_delete_rpp_published(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $plan = LearningPlan::factory()->create([
            'teacher_id' => $guru->id,
            'topic'      => 'RPP Published',
            'status'     => PlanStatus::Published,
        ]);

        $response = $this->actingAs($guru)
            ->post(route('plans.bulk-destroy'), [
                'ids' => [$plan->id],
            ]);

        $response->assertRedirect(route('plans.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('learning_plans', ['id' => $plan->id, 'deleted_at' => null]);
    }

    public function test_guru_tidak_bisa_bulk_delete_rpp_milik_guru_lain(): void
    {
        $guru    = User::where('email', 'naya@aksara.test')->firstOrFail();
        $guruB   = User::where('email', 'admin@aksara.test')->firstOrFail();

        $plan = LearningPlan::factory()->create([
            'teacher_id' => $guruB->id,
            'topic'      => 'RPP Guru Lain',
            'status'     => PlanStatus::Draft,
        ]);

        $response = $this->actingAs($guru)
            ->post(route('plans.bulk-destroy'), [
                'ids' => [$plan->id],
            ]);

        // Karena forCurrentUser() scope mengecualikan plan milik $guruB dari pandangan $guru,
        // query akan kosong → back() atau redirect ke index, plan harus tetap ada
        $response->assertStatus(302);
        $this->assertDatabaseHas('learning_plans', ['id' => $plan->id, 'deleted_at' => null]);
    }

    public function test_admin_bisa_bulk_delete_rpp_draf_siapapun(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $guru  = User::where('email', 'naya@aksara.test')->firstOrFail();

        $plan = LearningPlan::factory()->create([
            'teacher_id' => $guru->id,
            'topic'      => 'RPP Guru untuk Admin Delete',
            'status'     => PlanStatus::Draft,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('plans.bulk-destroy'), [
                'ids' => [$plan->id],
            ]);

        $response->assertRedirect(route('plans.index'));
        $response->assertSessionHas('message');

        $this->assertSoftDeleted('learning_plans', ['id' => $plan->id]);
    }

    public function test_tamu_tidak_bisa_akses_bulk_destroy(): void
    {
        $response = $this->post(route('plans.bulk-destroy'), ['ids' => [1]]);

        $response->assertRedirect('/login');
    }
}
