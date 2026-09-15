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

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceBulkActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_admin_bisa_bulk_delete_rombel_tanpa_rpp(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $year = AcademicYear::firstOrFail();

        $rombel1 = SchoolClass::create([
            'academic_year_id' => $year->id,
            'name' => 'Kelas Uji A',
            'rombel_code' => 'UJI-A',
            'grade' => 7,
        ]);
        $rombel2 = SchoolClass::create([
            'academic_year_id' => $year->id,
            'name' => 'Kelas Uji B',
            'rombel_code' => 'UJI-B',
            'grade' => 7,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('references.rombels.bulk-destroy'), [
                'ids' => [$rombel1->id, $rombel2->id],
            ]);

        $response->assertRedirect(route('references.index', ['tab' => 'rombel']));
        $response->assertSessionHas('message');

        $this->assertDatabaseMissing('school_classes', ['id' => $rombel1->id]);
        $this->assertDatabaseMissing('school_classes', ['id' => $rombel2->id]);
    }

    public function test_rombel_dengan_rpp_dilindungi_dari_bulk_delete(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        // Rombel yang memiliki RPP dari seeder (7A memiliki RPP)
        $rombelWithPlan = SchoolClass::whereHas('learningPlans')->firstOrFail();

        $response = $this->actingAs($admin)
            ->post(route('references.rombels.bulk-destroy'), [
                'ids' => [$rombelWithPlan->id],
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('school_classes', ['id' => $rombelWithPlan->id]);
    }

    public function test_admin_bisa_bulk_delete_mapel_tanpa_ketergantungan(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $mapel1 = Subject::create([
            'name' => 'Robotika Dasar',
            'code' => 'ROB1',
            'phase' => 'D',
            'jenjang' => 'SMP',
        ]);
        $mapel2 = Subject::create([
            'name' => 'Desain Komunikasi Visual',
            'code' => 'DKV1',
            'phase' => 'D',
            'jenjang' => 'SMP',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('references.mapel.bulk-destroy'), [
                'ids' => [$mapel1->id, $mapel2->id],
            ]);

        $response->assertRedirect(route('references.index', ['tab' => 'mapel']));
        $response->assertSessionHas('message');

        $this->assertDatabaseMissing('subjects', ['id' => $mapel1->id]);
        $this->assertDatabaseMissing('subjects', ['id' => $mapel2->id]);
    }

    public function test_mapel_dengan_rpp_atau_kurikulum_dilindungi_dari_bulk_delete(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        // Subject Informatika (INF) memiliki CP/ATP dan Learning Plans dari seeder
        $subject = Subject::where('code', 'INF')->firstOrFail();

        $response = $this->actingAs($admin)
            ->post(route('references.mapel.bulk-destroy'), [
                'ids' => [$subject->id],
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
    }

    public function test_guru_tidak_bisa_bulk_delete_rombel_atau_mapel(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $response = $this->actingAs($guru)
            ->post(route('references.rombels.bulk-destroy'), [
                'ids' => [1],
            ]);
        $response->assertForbidden();

        $responseMapel = $this->actingAs($guru)
            ->post(route('references.mapel.bulk-destroy'), [
                'ids' => [1],
            ]);
        $responseMapel->assertForbidden();
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $response = $this->post(route('references.rombels.bulk-destroy'), [
            'ids' => [1],
        ]);
        $response->assertRedirect(route('login'));

        $responseMapel = $this->post(route('references.mapel.bulk-destroy'), [
            'ids' => [1],
        ]);
        $responseMapel->assertRedirect(route('login'));
    }
}
