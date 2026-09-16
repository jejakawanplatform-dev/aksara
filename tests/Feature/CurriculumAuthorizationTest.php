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
use App\Models\CurriculumCp;
use App\Models\CurriculumTp;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\InformatikaCurriculumSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InformatikaCurriculumSeeder::class);
        $this->seed(DemoDataSeeder::class);
    }

    public function test_guru_tidak_dapat_mengubah_cp_mapel_lain_meskipun_target_mapel_miliknya(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail(); // guru Informatika
        $matematika = Subject::where('code', 'MTK')->firstOrFail();
        $informatika = Subject::where('code', 'INF')->firstOrFail();

        // Buat CP pada Matematika (yang bukan diampu oleh guru)
        $mtkCp = CurriculumCp::create([
            'subject_id' => $matematika->id,
            'phase' => 'D',
            'element_code' => 'ALJ',
            'element_name' => 'Aljabar',
            'statement' => 'Memahami konsep aljabar dasar.',
            'sequence' => 1,
        ]);

        // Upaya IDOR: Guru mencoba update CP Matematika dengan target subject_id Informatika
        $response = $this->actingAs($guru)->put(route('references.cps.update', $mtkCp), [
            'subject_id' => $informatika->id,
            'phase' => 'D',
            'element_code' => 'ALJ',
            'element_name' => 'Aljabar Bajakan',
            'statement' => 'Statement baru',
            'sequence' => 1,
        ]);

        $response->assertStatus(403);
        $mtkCp->refresh();
        $this->assertEquals($matematika->id, $mtkCp->subject_id);
        $this->assertEquals('Aljabar', $mtkCp->element_name);
    }

    public function test_guru_tidak_dapat_mengubah_tp_asal_mapel_lain_meskipun_target_cp_mapel_miliknya(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $matematika = Subject::where('code', 'MTK')->firstOrFail();
        $informatika = Subject::where('code', 'INF')->firstOrFail();

        $mtkCp = CurriculumCp::create([
            'subject_id' => $matematika->id,
            'phase' => 'D',
            'element_code' => 'ALJ',
            'element_name' => 'Aljabar',
            'statement' => 'Memahami konsep aljabar.',
            'sequence' => 1,
        ]);

        $mtkTp = CurriculumTp::create([
            'curriculum_cp_id' => $mtkCp->id,
            'code' => 'ALJ-01',
            'statement' => 'Menyelesaikan persamaan linier.',
            'sequence' => 1,
        ]);

        $infCp = CurriculumCp::where('subject_id', $informatika->id)->firstOrFail();

        // Upaya re-parenting TP dari CP Matematika ke CP Informatika oleh guru Informatika
        $response = $this->actingAs($guru)->put(route('references.tps.update', $mtkTp), [
            'curriculum_cp_id' => $infCp->id,
            'code' => 'ALJ-01-MUTATED',
            'statement' => 'Statement mutated',
            'sequence' => 1,
        ]);

        $response->assertStatus(403);
        $mtkTp->refresh();
        $this->assertEquals($mtkCp->id, $mtkTp->curriculum_cp_id);
    }

    public function test_atp_menolak_tp_yang_bukan_milik_mapel_terpilih(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $matematika = Subject::where('code', 'MTK')->firstOrFail();
        $informatika = Subject::where('code', 'INF')->firstOrFail();
        $year = AcademicYear::firstOrFail();

        $mtkCp = CurriculumCp::create([
            'subject_id' => $matematika->id,
            'phase' => 'D',
            'element_code' => 'ALJ',
            'element_name' => 'Aljabar',
            'statement' => 'Memahami konsep aljabar.',
            'sequence' => 1,
        ]);

        $mtkTp = CurriculumTp::create([
            'curriculum_cp_id' => $mtkCp->id,
            'code' => 'ALJ-01',
            'statement' => 'Menyelesaikan persamaan linier.',
            'sequence' => 1,
        ]);

        // Coba membuat ATP Informatika menggunakan TP Matematika
        $response = $this->actingAs($admin)->post(route('references.atp.store'), [
            'subject_id' => $informatika->id,
            'academic_year_id' => $year->id,
            'curriculum_tp_id' => $mtkTp->id,
            'grade' => 7,
            'sequence' => 99,
        ]);

        $response->assertSessionHasErrors('curriculum_tp_id');
    }
}
