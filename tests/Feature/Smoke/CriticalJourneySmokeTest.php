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

namespace Tests\Feature\Smoke;

use App\Enums\AttendanceStatus;
use App\Models\LearningMaterial;
use App\Models\LearningPlan;
use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CriticalJourneySmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
        $this->seed(SystemSettingSeeder::class);
    }

    /**
     * Smoke test memvalidasi alur kritis bersambung 5 role dalam satu skenario sekolah:
     * Admin ➔ Guru ➔ Siswa ➔ Guru ➔ Wali Kelas ➔ Wali Murid
     */
    public function test_alur_kritis_lima_role_sekolah_berjalan_mulus(): void
    {
        // ─── 1. TAHAP ADMIN: Akses & Pengawasan Sistem ───
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard/Admin'));

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Users/Index')->has('users.data'));

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Settings/Index')->has('providers'));

        $this->actingAs($admin)
            ->get(route('references.index'))
            ->assertOk();

        // ─── 2. TAHAP GURU: Akses RPP & Materi Pembelajaran ───
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $this->actingAs($guru)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard/Guru'));

        $this->actingAs($guru)
            ->get(route('plans.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Plans/Index')->has('plans.data'));

        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();
        $material = LearningMaterial::where('plan_id', $plan->id)->firstOrFail();

        // Guru membuka editor materi
        $this->actingAs($guru)
            ->get(route('materials.edit', $material))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Materials/Edit')->has('material.id'));

        // ─── 3. TAHAP SISWA: Belajar & Mengerjakan Kuis ───
        $siswa = User::where('email', 'adit@aksara.test')->firstOrFail();

        $this->actingAs($siswa)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard/Siswa'));

        // Siswa membuka materi pembelajaran
        $this->actingAs($siswa)
            ->get(route('materials.show', $material))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Materials/Show'));

        // Event membaca materi tercatat di database
        $this->assertDatabaseHas('learning_events', [
            'material_id' => $material->id,
            'student_id' => $siswa->id,
        ]);

        // Siswa mengerjakan kuis jika kuis tersedia
        $quiz = Quiz::where('plan_id', $plan->id)->first();
        if ($quiz) {
            $this->actingAs($siswa)
                ->get(route('quiz.attempt', $quiz))
                ->assertOk()
                ->assertInertia(fn ($page) => $page->component('Quiz/Attempt'));

            // Submit jawaban kuis
            $questions = $quiz->questions ?? [];
            $answers = [];
            foreach ($questions as $i => $q) {
                $answers[$i] = $q['correct_answer'] ?? ($q['options'][0] ?? 'Pilihan');
            }

            $submitResponse = $this->actingAs($siswa)
                ->post(route('quiz.attempt.submit', $quiz), [
                    'answers' => $answers,
                ]);

            $submitResponse->assertSessionHasNoErrors();
            $submitResponse->assertRedirect();
            $this->assertDatabaseHas('quiz_attempts', [
                'quiz_id' => $quiz->id,
                'student_id' => $siswa->id,
            ]);
        }

        // ─── 4. TAHAP GURU: Input Kehadiran & Evaluasi Refleksi ───
        $this->actingAs($guru)
            ->post(route('attendance.save', $plan), [
                'attendance' => [
                    $siswa->id => [
                        'status' => AttendanceStatus::Present->value,
                        'notes' => 'Hadir aktif di kelas',
                    ],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('attendance_records', [
            'plan_id' => $plan->id,
            'student_id' => $siswa->id,
            'status' => AttendanceStatus::Present->value,
        ]);

        $this->actingAs($guru)
            ->post(route('evaluation.save', $plan), [
                'notes' => 'Pembelajaran berjalan lancar dan siswa memahami konsep dekomposisi dengan sangat baik.',
                'challenges' => 'Beberapa siswa butuh waktu lebih lama saat latihan mandiri.',
                'next_action' => 'Memberikan pendampingan tambahan pada sesi praktikum berikutnya.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('teacher_evaluations', [
            'plan_id' => $plan->id,
            'teacher_id' => $guru->id,
        ]);

        // ─── 5. TAHAP WALI KELAS: Rekap Kehadiran ───
        $waliKelas = User::where('email', 'arif@aksara.test')->firstOrFail();

        $this->actingAs($waliKelas)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard/WaliKelas'));

        $this->actingAs($waliKelas)
            ->get(route('attendance.summary'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Attendance/Summary'));

        // ─── 6. TAHAP WALI MURID: Pemantauan Capaian Anak ───
        $waliMurid = User::where('email', 'ortu.adit@aksara.test')->firstOrFail();

        $this->actingAs($waliMurid)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard/WaliMurid')
                ->has('childData')
            );
    }
}
