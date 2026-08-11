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
use App\Models\LearningPlan;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_tab_switch_bekerja(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $this->actingAs($guru)
            ->get(route('references.index', ['tab' => 'tahun']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('References/Index')
                ->where('tab', 'tahun')
            );

        $this->actingAs($guru)
            ->get(route('references.index', ['tab' => 'semester']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('tab', 'semester')
                ->has('semesters')
            );

        $this->actingAs($guru)
            ->get(route('references.index', ['tab' => 'cp']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('tab', 'cp')
                ->has('cps')
            );
    }

    public function test_guru_tidak_bisa_akses_tab_profil_atau_operasional(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $this->actingAs($guru)
            ->get(route('references.index', ['tab' => 'profil']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('tab', 'tahun'));

        $this->actingAs($guru)
            ->get(route('references.index', ['tab' => 'operasional']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('tab', 'tahun'));
    }

    public function test_crud_tahun_ajaran_admin(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('references.years.store'), [
                'name' => '2026/2027',
                'code' => '2026-2027',
                'is_active' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('academic_years', ['code' => '2026-2027']);

        $year = AcademicYear::where('code', '2026-2027')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('references.years.update', $year), [
                'name' => '2026/2027 Revisi',
                'code' => '2026-2027',
                'is_active' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('academic_years', ['code' => '2026-2027', 'name' => '2026/2027 Revisi']);

        $this->actingAs($admin)
            ->delete(route('references.years.destroy', $year))
            ->assertRedirect();

        $this->assertDatabaseMissing('academic_years', ['code' => '2026-2027']);
    }

    public function test_guru_bisa_crud_cp_tp_mapel_diampu(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $infSubject = Subject::where('code', 'INF')->firstOrFail();

        $this->actingAs($guru)
            ->post(route('references.cps.store'), [
                'subject_id' => $infSubject->id,
                'phase' => 'D',
                'element_code' => 'X1',
                'element_name' => 'Elemen Uji',
                'statement' => 'Pernyataan CP uji untuk bimtek.',
                'sequence' => 1,
            ])
            ->assertRedirect();

        $cp = CurriculumCp::where('element_code', 'X1')->firstOrFail();

        $this->actingAs($guru)
            ->post(route('references.tps.store'), [
                'curriculum_cp_id' => $cp->id,
                'code' => 'X1-VII-01',
                'statement' => 'TP uji dekomposisi.',
                'grade' => 7,
                'sequence' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('curriculum_tps', ['code' => 'X1-VII-01']);

        $tp = CurriculumTp::where('code', 'X1-VII-01')->firstOrFail();

        $this->actingAs($guru)
            ->delete(route('references.tps.destroy', $tp))
            ->assertRedirect();

        $this->assertDatabaseMissing('curriculum_tps', ['code' => 'X1-VII-01']);
    }

    public function test_tidak_hapus_tahun_yang_masih_dipakai(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $year = AcademicYear::active();
        $this->assertNotNull($year);
        $this->assertTrue(SchoolClass::where('academic_year_id', $year->id)->exists());

        $this->actingAs($admin)
            ->delete(route('references.years.destroy', $year))
            ->assertRedirect()
            ->assertSessionHas('error', 'Tahun ajaran masih punya semester, rombel, atau rencana — hapus/pindahkan dulu.');

        $this->assertDatabaseHas('academic_years', ['id' => $year->id]);
    }

    public function test_aktifkan_semester(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $genap = Semester::where('code', 'genap')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('references.semesters.activate', $genap))
            ->assertRedirect();

        $this->assertTrue($genap->fresh()->is_active);
        $this->assertFalse(Semester::where('code', 'ganjil')->firstOrFail()->is_active);
    }

    public function test_filter_atp_per_kelas(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $this->actingAs($guru)
            ->get(route('references.index', ['tab' => 'atp', 'atpGradeFilter' => 8]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('tab', 'atp')
                ->has('atp')
                ->where('atp.data.0.tpCode', 'BK-VIII-01')
            );

        $this->actingAs($guru)
            ->get(route('references.index', ['tab' => 'atp', 'atpGradeFilter' => 9]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('atp.data.0.tpCode', 'BK-IX-01')
            );
    }

    public function test_kelola_anggota_rombel_admin(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $viii = SchoolClass::where('rombel_code', 'VIII-A')->firstOrFail();
        $siswa = User::where('email', 'eka@aksara.test')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('references.rombels.attach-student', $viii), [
                'student_id' => $siswa->id,
            ])
            ->assertRedirect();

        $this->assertTrue($viii->students()->where('users.id', $siswa->id)->exists());

        $this->actingAs($admin)
            ->delete(route('references.rombels.detach-student', [$viii, $siswa]))
            ->assertRedirect();

        $this->assertFalse($viii->fresh()->students()->where('users.id', $siswa->id)->exists());
    }

    public function test_guru_bisa_enrol_dan_batal_enrol_kelas_ajar(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $ixClass = SchoolClass::where('rombel_code', 'IX-A')->firstOrFail();

        $this->actingAs($guru)
            ->post(route('references.rombels.enrol', $ixClass))
            ->assertRedirect()
            ->assertSessionHas('message', 'Berhasil mendaftarkan (enrol) kelas ajar baru!');

        $this->assertTrue(LearningPlan::where('teacher_id', $guru->id)->where('class_id', $ixClass->id)->exists());

        $this->actingAs($guru)
            ->post(route('references.rombels.enrol', $ixClass))
            ->assertRedirect()
            ->assertSessionHas('message', 'Berhasil membatalkan enrolment kelas ajar.');

        $this->assertFalse(LearningPlan::where('teacher_id', $guru->id)->where('class_id', $ixClass->id)->exists());
    }

    public function test_admin_bisa_plotting_guru_pengampu_mapel(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $mtk = Subject::where('code', 'MTK')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('references.mapel.teachers', $mtk), [
                'teacher_ids' => [$guru->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('message', 'Plotting guru pengampu mata pelajaran berhasil disimpan.');

        $this->assertTrue($mtk->teachers()->where('users.id', $guru->id)->exists());
    }
}
