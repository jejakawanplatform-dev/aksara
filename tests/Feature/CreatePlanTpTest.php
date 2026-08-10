<?php

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

class CreatePlanTpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_pilih_tp_mengisi_referensi_dan_tersimpan(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $tp = CurriculumTp::where('code', 'BK-VII-01')->firstOrFail();
        $class = SchoolClass::where('rombel_code', 'VII-A')->firstOrFail();
        $subject = Subject::where('code', 'INF')->firstOrFail();
        $cp = CurriculumCp::where('element_code', 'BK')->firstOrFail();
        $year = AcademicYear::active() ?? AcademicYear::query()->firstOrFail();
        $semester = Semester::query()->where('academic_year_id', $year->id)->firstOrFail();

        $this->actingAs($guru)->post(route('plans.store'), [
            'mode' => 'manual',
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'curriculum_cp_id' => $cp->id,
            'curriculum_tp_id' => $tp->id,
            'phase' => 'D',
            'grade' => 7,
            'topic' => 'Uji TP Dekomposisi',
            'duration_minutes' => 80,
            'learning_objectives' => 'Tujuan uji',
            'student_needs' => '',
            'curriculum_reference' => '',
        ])->assertRedirect(route('plans.index'));

        $this->assertDatabaseHas('learning_plans', [
            'topic' => 'Uji TP Dekomposisi',
            'curriculum_tp_id' => $tp->id,
            'curriculum_cp_id' => $cp->id,
        ]);

        $plan = LearningPlan::where('topic', 'Uji TP Dekomposisi')->firstOrFail();
        $this->assertStringContainsString('BK-VII-01', $plan->curriculum_reference);
    }
}
