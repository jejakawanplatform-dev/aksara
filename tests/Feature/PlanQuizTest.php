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
use App\Enums\UserRole;
use App\Models\LearningPlan;
use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanQuizTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
        $this->seed(SystemSettingSeeder::class);
    }

    public function test_guru_dapat_menyimpan_kuis_baru_dan_update_by_id(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();

        $payload = [
            'id' => null,
            'title' => 'Kuis Unit Test Awal',
            'status' => 'draft',
            'questions' => [
                [
                    'question' => 'Apa itu dekomposisi?',
                    'options' => ['Memecah masalah', 'Menggabung data', 'Menghapus file', 'Mencetak laporan'],
                    'correct_answer' => 'Memecah masalah',
                ],
            ],
        ];

        $this->actingAs($guru)
            ->post(route('plans.quiz.store', $plan), $payload)
            ->assertRedirect();

        $quiz = Quiz::query()
            ->where('plan_id', $plan->id)
            ->where('title', 'Kuis Unit Test Awal')
            ->first();

        $this->assertNotNull($quiz);
        $this->assertSame('draft', $quiz->status);

        $rename = [
            'id' => $quiz->id,
            'title' => 'Kuis Unit Test Diganti Judul',
            'status' => 'published',
            'questions' => [
                [
                    'question' => 'Apa itu dekomposisi?',
                    'options' => ['Memecah masalah', 'Menggabung data', 'Menghapus file', 'Mencetak laporan'],
                    'correct_answer' => 'Memecah masalah',
                ],
                [
                    'question' => 'Contoh abstraksi?',
                    'options' => ['Fokus fitur penting', 'Copy-paste', 'Hardcode', 'Ignore error'],
                    'correct_answer' => 'Fokus fitur penting',
                ],
            ],
        ];

        $this->actingAs($guru)
            ->post(route('plans.quiz.store', $plan), $rename)
            ->assertRedirect();

        $quiz->refresh();
        $this->assertSame('Kuis Unit Test Diganti Judul', $quiz->title);
        $this->assertSame('published', $quiz->status);
        $this->assertCount(2, $quiz->questions);

        $this->assertSame(
            1,
            Quiz::query()->where('plan_id', $plan->id)->where('title', 'Kuis Unit Test Diganti Judul')->count()
        );
        $this->assertSame(
            0,
            Quiz::query()->where('plan_id', $plan->id)->where('title', 'Kuis Unit Test Awal')->count()
        );
    }

    public function test_edit_page_menyertakan_id_kuis_terbaru(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();

        $quiz = Quiz::query()->create([
            'plan_id' => $plan->id,
            'title' => 'Kuis Seed Form Terbaru',
            'status' => 'draft',
            'questions' => [
                [
                    'question' => 'Soal satu',
                    'options' => ['A', 'B', 'C', 'D'],
                    'correct_answer' => 'A',
                ],
            ],
        ]);

        $this->actingAs($guru)
            ->get(route('plans.quiz', $plan))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Quiz/Form')
                ->where('form.id', $quiz->id)
                ->where('form.title', 'Kuis Seed Form Terbaru')
            );
    }

    public function test_update_id_kuis_plan_lain_ditolak(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();

        $otherTeacher = User::factory()->create([
            'role' => UserRole::Teacher,
            'email_verified_at' => now(),
        ]);
        $otherTeacher->syncAppRole();

        $otherPlan = LearningPlan::create([
            'teacher_id' => $otherTeacher->id,
            'academic_year_id' => $plan->academic_year_id,
            'semester_id' => $plan->semester_id,
            'class_id' => $plan->class_id,
            'subject_id' => $plan->subject_id,
            'phase' => $plan->phase,
            'grade' => $plan->grade,
            'topic' => 'Rencana Guru Lain Untuk Isolasi Kuis',
            'duration_minutes' => 40,
            'learning_objectives' => 'Demo isolasi',
            'curriculum_reference' => 'Demo',
            'status' => PlanStatus::Draft,
        ]);

        $foreignQuiz = Quiz::query()->create([
            'plan_id' => $otherPlan->id,
            'title' => 'Kuis Plan Lain',
            'status' => 'draft',
            'questions' => [
                [
                    'question' => 'Soal asing',
                    'options' => ['A', 'B', 'C', 'D'],
                    'correct_answer' => 'A',
                ],
            ],
        ]);

        $this->actingAs($guru)
            ->post(route('plans.quiz.store', $plan), [
                'id' => $foreignQuiz->id,
                'title' => 'Hijack',
                'status' => 'draft',
                'questions' => [
                    [
                        'question' => 'Soal',
                        'options' => ['A', 'B', 'C', 'D'],
                        'correct_answer' => 'A',
                    ],
                ],
            ])
            ->assertNotFound();
    }
}
