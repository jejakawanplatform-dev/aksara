<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\LearningPlan;
use App\Models\SchoolClass;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeroomDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_dashboard_wali_kelas_memuat_metrik_dan_rekap_kelas(): void
    {
        $homeroom = User::where('email', 'arif@aksara.test')->firstOrFail();
        $class = SchoolClass::where('homeroom_teacher_id', $homeroom->id)->firstOrFail();
        $student = $class->students()->firstOrFail();
        $plan = LearningPlan::where('class_id', $class->id)->firstOrFail();

        AttendanceRecord::create([
            'plan_id' => $plan->id,
            'student_id' => $student->id,
            'status' => AttendanceStatus::Present,
        ]);

        $this->actingAs($homeroom)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard/WaliKelas')
                ->has('metrics')
                ->where('metrics.classesCount', 1)
                ->where('metrics.studentsCount', fn ($n) => $n >= 1)
                ->where('classes.0.id', $class->id)
                ->where('classes.0.pctHadir', 100)
                ->where('classes.0.attendanceSummaryUrl', route('attendance.summary', ['classId' => $class->id]))
                ->where('classes.0.attentionStudents', fn ($rows) => collect($rows)->contains(
                    fn ($row) => $row['id'] === $student->id && $row['pctHadir'] === 100
                ))
            );
    }
}
