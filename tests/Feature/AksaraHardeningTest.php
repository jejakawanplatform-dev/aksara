<?php

namespace Tests\Feature;

use App\Enums\MaterialStatus;
use App\Enums\PlanStatus;
use App\Enums\UserRole;
use App\Models\LearningMaterial;
use App\Models\LearningPlan;
use App\Models\Quiz;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AksaraHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_siswa_melihat_materi_published_kelasnya(): void
    {
        $siswa = User::where('email', 'adit@aksara.test')->firstOrFail();
        $material = LearningMaterial::where('status', MaterialStatus::Published)->firstOrFail();

        $this->actingAs($siswa)
            ->get(route('materials.show', $material))
            ->assertOk()
            ->assertSee('Dekomposisi');
    }

    public function test_siswa_tidak_melihat_saran_ilustrasi_dan_prompt_ai(): void
    {
        $siswa = User::where('email', 'adit@aksara.test')->firstOrFail();
        $material = LearningMaterial::where('status', MaterialStatus::Published)->firstOrFail();

        $content = is_array($material->content) ? $material->content : [];
        $sections = $content['sections'] ?? [];
        $sections[0] = [
            'heading' => $sections[0]['heading'] ?? 'Seksi 1',
            'body' => '<p>Isi bacaan siswa tentang dekomposisi.</p>'
                .'<blockquote>'
                .'<p><strong>🖼️ Saran Ilustrasi:</strong> Diagram dekomposisi</p>'
                .'<p><strong>🎯 Prompt AI Image:</strong> <code>decomposition flowchart textbook</code></p>'
                .'<p><a href="https://unsplash.com/s/photos/flowchart">Cari &amp; unduh di Unsplash</a></p>'
                .'</blockquote>',
        ];
        $content['sections'] = $sections;
        $material->update(['content' => $content]);

        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $this->actingAs($guru)
            ->get(route('materials.show', $material))
            ->assertOk()
            ->assertSee('Prompt AI Image', false)
            ->assertSee('decomposition flowchart textbook', false);

        $this->actingAs($siswa)
            ->get(route('materials.show', $material))
            ->assertOk()
            ->assertSee('Isi bacaan siswa tentang dekomposisi.', false)
            ->assertDontSee('Saran Ilustrasi', false)
            ->assertDontSee('Prompt AI Image', false)
            ->assertDontSee('decomposition flowchart textbook', false)
            ->assertDontSee('Cari & unduh di Unsplash', false);
    }

    public function test_siswa_tidak_bisa_buka_materi_draft(): void
    {
        $siswa = User::where('email', 'adit@aksara.test')->firstOrFail();
        $plan = LearningPlan::firstOrFail();

        $draft = LearningMaterial::create([
            'plan_id' => $plan->id,
            'content' => ['title' => 'Rahasia', 'sections' => []],
            'status' => MaterialStatus::Draft,
        ]);

        // Soft-delete published material relation is hasOne — create separate draft on same plan
        // may conflict; use another plan owned by teacher
        $draft->delete();

        $otherPlan = LearningPlan::create([
            'teacher_id' => $plan->teacher_id,
            'class_id' => $plan->class_id,
            'subject_id' => $plan->subject_id,
            'phase' => 'D',
            'grade' => 7,
            'topic' => 'Draft Only',
            'duration_minutes' => 40,
            'learning_objectives' => 'Demo',
            'curriculum_reference' => 'Demo',
            'status' => PlanStatus::Draft,
        ]);

        $draftMaterial = LearningMaterial::create([
            'plan_id' => $otherPlan->id,
            'content' => ['title' => 'Rahasia', 'sections' => [['heading' => 'X', 'body' => 'Y']]],
            'status' => MaterialStatus::Draft,
        ]);

        $this->actingAs($siswa)
            ->get(route('materials.show', $draftMaterial))
            ->assertForbidden();
    }

    public function test_guru_lain_tidak_bisa_buka_draft_rencana(): void
    {
        $plan = LearningPlan::firstOrFail();
        $other = User::create([
            'name' => 'Guru Lain',
            'email' => 'lain@aksara.test',
            'password' => 'password',
            'role' => UserRole::Teacher,
        ]);

        $this->actingAs($other)
            ->get(route('plans.draft', $plan))
            ->assertForbidden();
    }

    public function test_laporan_guru_tidak_crash(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $this->actingAs($guru)
            ->get(route('reports.guru'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reports/Teacher')
                ->has('reportData')
                ->where('reportData.0.topic', fn ($topic) => str_contains($topic, 'Dekomposisi'))
            );
    }

    public function test_homeroom_tidak_bisa_buat_rencana(): void
    {
        $homeroom = User::where('email', 'arif@aksara.test')->firstOrFail();

        $this->actingAs($homeroom)
            ->get(route('plans.create'))
            ->assertForbidden();
    }

    public function test_homeroom_bisa_rekap_kehadiran(): void
    {
        $homeroom = User::where('email', 'arif@aksara.test')->firstOrFail();

        $this->actingAs($homeroom)
            ->get(route('attendance.summary'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Attendance/Summary'));
    }

    public function test_siswa_bisa_akses_kuis_published(): void
    {
        $siswa = User::where('email', 'adit@aksara.test')->firstOrFail();
        $quiz = Quiz::where('status', 'published')->firstOrFail();

        $this->actingAs($siswa)
            ->get(route('quiz.attempt', $quiz))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Quiz/Attempt')
                ->where('quiz.title', fn ($title) => str_contains($title, 'Dekomposisi'))
            );
    }

    public function test_guru_bisa_buka_referensi_kurikulum(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $this->actingAs($guru)
            ->get(route('references.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('References/Index')
                ->has('tabs')
                ->has('years')
                ->where('years.0.name', '2025/2026')
            );

        $this->actingAs($guru)
            ->get(route('references.index', ['tab' => 'semester']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('tab', 'semester')
                ->where('semesters.0.name', 'Ganjil')
            );

        $this->actingAs($guru)
            ->get(route('references.index', ['tab' => 'cp']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('tab', 'cp')
                ->has('cps')
                ->where('cps.0.element_name', 'Berpikir Komputasional')
                ->where('cps.0.tps.0.code', 'BK-VII-01')
            );
    }

    public function test_siswa_tidak_lihat_materi_kelas_lain(): void
    {
        $siswa = User::where('email', 'adit@aksara.test')->firstOrFail();
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $subject = Subject::firstOrFail();

        $otherClass = SchoolClass::create([
            'name' => 'VII-B',
            'grade' => 7,
            'homeroom_teacher_id' => User::where('email', 'arif@aksara.test')->value('id'),
        ]);

        $plan = LearningPlan::create([
            'teacher_id' => $guru->id,
            'class_id' => $otherClass->id,
            'subject_id' => $subject->id,
            'phase' => 'D',
            'grade' => 7,
            'topic' => 'Khusus VII-B',
            'duration_minutes' => 40,
            'learning_objectives' => 'Demo',
            'curriculum_reference' => 'Demo',
            'status' => PlanStatus::Published,
        ]);

        $material = LearningMaterial::create([
            'plan_id' => $plan->id,
            'content' => ['title' => 'Khusus VII-B', 'sections' => []],
            'status' => MaterialStatus::Published,
            'published_at' => now(),
        ]);

        $this->actingAs($siswa)
            ->get(route('materials.show', $material))
            ->assertForbidden();

        $this->actingAs($siswa)
            ->get(route('materials.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Materials/Index')
                ->where('materials', fn ($rows) => ! collect($rows)->contains(
                    fn ($row) => ($row['title'] ?? '') === 'Khusus VII-B'
                ))
            );
    }
}
