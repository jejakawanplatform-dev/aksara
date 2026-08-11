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
use App\Models\LearningPlan;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceEvaluationInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_form_absensi_inertia(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();

        $this->actingAs($guru)
            ->get(route('attendance.form', $plan))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Attendance/Form')
                ->where('plan.id', $plan->id)
                ->has('students')
                ->has('statuses')
                ->has('saveUrl')
            );
    }

    public function test_simpan_absensi_upsert(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();
        $plan->load('class.students');
        $student = $plan->class->students->firstOrFail();

        $this->actingAs($guru)
            ->post(route('attendance.save', $plan), [
                'attendance' => [
                    $student->id => [
                        'status' => AttendanceStatus::Present->value,
                        'notes' => '',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendance_records', [
            'plan_id' => $plan->id,
            'student_id' => $student->id,
            'status' => AttendanceStatus::Present->value,
        ]);
    }

    public function test_form_evaluasi_inertia(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();

        $this->actingAs($guru)
            ->get(route('evaluation.form', $plan))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Evaluation/Form')
                ->where('plan.id', $plan->id)
                ->has('form.notes')
                ->has('saveUrl')
            );
    }
}
