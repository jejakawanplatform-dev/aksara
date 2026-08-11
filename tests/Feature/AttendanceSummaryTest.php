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
use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\LearningPlan;
use App\Models\SchoolClass;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_wali_kelas_hanya_melihat_kelas_homeroom(): void
    {
        $homeroom = User::where('email', 'arif@aksara.test')->firstOrFail();
        $ownClass = SchoolClass::where('homeroom_teacher_id', $homeroom->id)->firstOrFail();
        $otherClass = SchoolClass::whereNull('homeroom_teacher_id')->firstOrFail();

        $this->actingAs($homeroom)
            ->get(route('attendance.summary'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Attendance/Summary')
                ->has('classes', 1)
                ->where('classes.0.id', $ownClass->id)
            );

        $this->actingAs($homeroom)
            ->get(route('attendance.summary', ['classId' => $otherClass->id]))
            ->assertForbidden();
    }

    public function test_wali_kelas_melihat_rekap_semua_rencana_di_kelasnya(): void
    {
        $homeroom = User::where('email', 'arif@aksara.test')->firstOrFail();
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $class = SchoolClass::where('homeroom_teacher_id', $homeroom->id)->firstOrFail();
        $student = $class->students()->firstOrFail();
        $basePlan = LearningPlan::where('class_id', $class->id)->firstOrFail();

        $extraPlan = LearningPlan::create([
            'teacher_id' => $guru->id,
            'academic_year_id' => $basePlan->academic_year_id,
            'semester_id' => $basePlan->semester_id,
            'class_id' => $class->id,
            'subject_id' => $basePlan->subject_id,
            'phase' => $basePlan->phase,
            'grade' => $basePlan->grade,
            'topic' => 'Rencana Tambahan Wali',
            'duration_minutes' => 40,
            'learning_objectives' => 'Demo',
            'curriculum_reference' => 'Demo',
            'status' => PlanStatus::Published,
        ]);

        AttendanceRecord::create([
            'plan_id' => $basePlan->id,
            'student_id' => $student->id,
            'status' => AttendanceStatus::Present,
        ]);
        AttendanceRecord::create([
            'plan_id' => $extraPlan->id,
            'student_id' => $student->id,
            'status' => AttendanceStatus::Absent,
        ]);

        $this->actingAs($homeroom)
            ->get(route('attendance.summary', ['classId' => $class->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Attendance/Summary')
                ->where('plans', fn ($plans) => count($plans) >= 2)
                ->where('summaryData.data', fn ($rows) => collect($rows)->contains(
                    fn ($row) => $row['studentId'] === $student->id
                        && $row['hadir'] === 1
                        && $row['alpha'] === 1
                        && $row['total'] === 2
                ))
            );
    }

    public function test_guru_mapel_tidak_bisa_filter_rencana_orang_lain(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $other = User::create([
            'name' => 'Guru Lain',
            'email' => 'guru.lain@aksara.test',
            'password' => 'password',
            'role' => UserRole::Teacher,
        ]);
        $other->syncAppRole();

        $ownPlan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();
        $foreignPlan = LearningPlan::create([
            'teacher_id' => $other->id,
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

        $this->actingAs($guru)
            ->get(route('attendance.summary', [
                'classId' => $ownPlan->class_id,
                'planId' => $foreignPlan->id,
            ]))
            ->assertForbidden();
    }
}
