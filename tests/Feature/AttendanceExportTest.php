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

use App\Enums\AttendanceStatus;
use App\Enums\PlanStatus;
use App\Models\AttendanceRecord;
use App\Models\LearningPlan;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\AttendanceExportService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_guest_dilarang_akses_ekspor_absensi(): void
    {
        $class = SchoolClass::firstOrFail();

        $this->get(route('attendance.export', ['format' => 'pdf', 'classId' => $class->id]))
            ->assertRedirect(route('login'));

        $this->get(route('attendance.export', ['format' => 'excel', 'classId' => $class->id]))
            ->assertRedirect(route('login'));
    }

    public function test_wali_kelas_dapat_ekspor_kelas_binaannya_pdf(): void
    {
        $homeroom = User::where('email', 'arif@aksara.test')->firstOrFail();
        $ownClass = SchoolClass::where('homeroom_teacher_id', $homeroom->id)->firstOrFail();

        $response = $this->actingAs($homeroom)
            ->get(route('attendance.export', ['format' => 'pdf', 'classId' => $ownClass->id]));

        $response->assertOk();
        $response->assertViewIs('exports.attendance-pdf');
        $response->assertSee('Rekapitulasi Kehadiran Siswa');
        $response->assertSee($ownClass->name);
    }

    public function test_wali_kelas_dapat_ekspor_kelas_binaannya_excel(): void
    {
        $homeroom = User::where('email', 'arif@aksara.test')->firstOrFail();
        $ownClass = SchoolClass::where('homeroom_teacher_id', $homeroom->id)->firstOrFail();

        $response = $this->actingAs($homeroom)
            ->get(route('attendance.export', ['format' => 'excel', 'classId' => $ownClass->id]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('attachment;', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);
    }

    public function test_wali_kelas_dilarang_ekspor_kelas_lain(): void
    {
        $homeroom = User::where('email', 'arif@aksara.test')->firstOrFail();
        $otherClass = SchoolClass::whereNull('homeroom_teacher_id')->firstOrFail();

        $this->actingAs($homeroom)
            ->get(route('attendance.export', ['format' => 'pdf', 'classId' => $otherClass->id]))
            ->assertForbidden();

        $this->actingAs($homeroom)
            ->get(route('attendance.export', ['format' => 'excel', 'classId' => $otherClass->id]))
            ->assertForbidden();
    }

    public function test_guru_dapat_ekspor_kelas_dan_rencana_ajar_miliknya(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();

        $response = $this->actingAs($guru)
            ->get(route('attendance.export', [
                'format' => 'pdf',
                'classId' => $plan->class_id,
                'planId' => $plan->id,
            ]));

        $response->assertOk();
        $response->assertViewIs('exports.attendance-pdf');

        $excelResponse = $this->actingAs($guru)
            ->get(route('attendance.export', [
                'format' => 'excel',
                'classId' => $plan->class_id,
                'planId' => $plan->id,
            ]));

        $excelResponse->assertOk();
    }

    public function test_guru_dilarang_ekspor_kelas_yang_tidak_diampunya(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $otherClass = SchoolClass::whereNull('homeroom_teacher_id')->firstOrFail();

        $this->actingAs($guru)
            ->get(route('attendance.export', ['format' => 'pdf', 'classId' => $otherClass->id]))
            ->assertForbidden();
    }

    public function test_guru_dilarang_ekspor_rencana_ajar_guru_lain(): void
    {
        $guruNaya = User::where('email', 'naya@aksara.test')->firstOrFail();
        $ownPlan = LearningPlan::where('teacher_id', $guruNaya->id)->firstOrFail();

        // Buat rencana milik guru lain di kelas yang sama
        $otherGuru = User::create([
            'name' => 'Guru Lain',
            'email' => 'guru.lain@aksara.test',
            'password' => 'password',
            'role' => \App\Enums\UserRole::Teacher,
        ]);
        $otherGuru->syncAppRole();

        $foreignPlan = LearningPlan::create([
            'teacher_id' => $otherGuru->id,
            'academic_year_id' => $ownPlan->academic_year_id,
            'semester_id' => $ownPlan->semester_id,
            'class_id' => $ownPlan->class_id,
            'subject_id' => $ownPlan->subject_id,
            'phase' => $ownPlan->phase,
            'grade' => $ownPlan->grade,
            'topic' => 'Rencana Guru Lain',
            'duration_minutes' => 40,
            'learning_objectives' => 'Demo',
            'curriculum_reference' => 'Demo',
            'status' => PlanStatus::Published,
        ]);

        $this->actingAs($guruNaya)
            ->get(route('attendance.export', [
                'format' => 'pdf',
                'classId' => $ownPlan->class_id,
                'planId' => $foreignPlan->id,
            ]))
            ->assertForbidden();
    }

    public function test_admin_dapat_ekspor_semua_kelas(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $class = SchoolClass::firstOrFail();

        $this->actingAs($admin)
            ->get(route('attendance.export', ['format' => 'pdf', 'classId' => $class->id]))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('attendance.export', ['format' => 'excel', 'classId' => $class->id]))
            ->assertOk();
    }

    public function test_format_ekspor_tidak_valid_mengembalikan_404(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $class = SchoolClass::firstOrFail();

        $this->actingAs($admin)
            ->get(route('attendance.export', ['format' => 'csv', 'classId' => $class->id]))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('attendance.export', ['format' => 'word', 'classId' => $class->id]))
            ->assertNotFound();
    }

    public function test_service_ekspor_menghasilkan_spreadsheet_dan_data_valid(): void
    {
        /** @var AttendanceExportService $service */
        $service = app(AttendanceExportService::class);
        $homeroom = User::where('email', 'arif@aksara.test')->firstOrFail();
        $class = SchoolClass::where('homeroom_teacher_id', $homeroom->id)->firstOrFail();
        $plans = LearningPlan::where('class_id', $class->id)->get();
        $planIds = $plans->pluck('id');

        $student = $class->students()->firstOrFail();

        // Rekam kehadiran
        if ($plans->isNotEmpty()) {
            AttendanceRecord::updateOrCreate(
                ['plan_id' => $plans->first()->id, 'student_id' => $student->id],
                ['status' => AttendanceStatus::Present]
            );
        }

        // 1. Build summary rows
        $rows = $service->buildSummaryRows($class, $planIds);
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);

        $studentRow = collect($rows)->firstWhere('studentId', $student->id);
        $this->assertNotNull($studentRow);
        $this->assertSame($student->name, $studentRow['studentName']);

        // 2. Export Excel binary
        $excelOutput = $service->exportExcel($class, $rows, $plans, null, $homeroom);
        $this->assertNotEmpty($excelOutput);
        // Header Zip/Excel dimulai dengan magic bytes "PK"
        $this->assertStringStartsWith('PK', $excelOutput);

        // 3. Prepare print data
        $printData = $service->preparePrintData($class, $rows, $plans, null, $homeroom);
        $this->assertArrayHasKey('class', $printData);
        $this->assertArrayHasKey('summaryRows', $printData);
        $this->assertArrayHasKey('stats', $printData);
        $this->assertArrayHasKey('schoolName', $printData);
    }
}
