<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AksaraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_user_role_label_benar(): void
    {
        $this->assertSame('Guru', UserRole::Teacher->label());
        $this->assertSame('Siswa', UserRole::Student->label());
        $this->assertSame('Wali Kelas', UserRole::HomeroomTeacher->label());
        $this->assertSame('Wali Murid', UserRole::Parent->label());
    }

    public function test_hanya_teacher_bisa_manage_plans(): void
    {
        $this->assertTrue(UserRole::Teacher->canManagePlans());
        $this->assertFalse(UserRole::Student->canManagePlans());
        $this->assertFalse(UserRole::Parent->canManagePlans());
    }

    public function test_teacher_dan_homeroom_bisa_lihat_laporan(): void
    {
        $this->assertTrue(UserRole::Teacher->canViewClassReport());
        $this->assertTrue(UserRole::HomeroomTeacher->canViewClassReport());
        $this->assertFalse(UserRole::Student->canViewClassReport());
        $this->assertFalse(UserRole::Parent->canViewClassReport());
    }

    public function test_user_model_helper_methods(): void
    {
        $teacher = new User(['role' => UserRole::Teacher]);
        $student = new User(['role' => UserRole::Student]);

        $this->assertTrue($teacher->isTeacher());
        $this->assertFalse($teacher->isStudent());
        $this->assertTrue($student->isStudent());
        $this->assertFalse($student->isTeacher());
    }

    public function test_tabel_users_punya_kolom_role(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('users', 'role'));
    }

    public function test_tabel_school_classes_ada(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('school_classes'));
    }

    public function test_tabel_learning_plans_ada(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('learning_plans'));
    }

    public function test_tabel_quizzes_ada(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('quizzes'));
    }

    public function test_guru_ibu_naya_terseed(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->first();
        $this->assertNotNull($guru);
        $this->assertSame(UserRole::Teacher, $guru->role);
        $this->assertTrue($guru->hasRole('teacher'));
    }

    public function test_kelas_viia_punya_5_siswa(): void
    {
        $kelas = SchoolClass::where('name', 'VII-A')->first();
        $this->assertNotNull($kelas);
        $this->assertSame(5, $kelas->students()->count());
    }

    public function test_mata_pelajaran_dan_cp_informatika_terseed(): void
    {
        $this->assertGreaterThanOrEqual(4, Subject::count());
        $this->assertDatabaseHas('subjects', ['code' => 'INF']);
        $this->assertSame(8, \App\Models\CurriculumCp::where('phase', 'D')->count());
        $this->assertDatabaseHas('curriculum_tps', ['code' => 'BK-VIII-01', 'grade' => 8]);
        $this->assertDatabaseHas('curriculum_tps', ['code' => 'BK-IX-01', 'grade' => 9]);
        $this->assertTrue(\App\Models\CurriculumAtpItem::where('grade', 8)->exists());
        $this->assertTrue(\App\Models\CurriculumAtpItem::where('grade', 9)->exists());
        $this->assertDatabaseHas('school_classes', ['rombel_code' => 'VIII-A', 'grade' => 8]);
        $this->assertDatabaseHas('school_classes', ['rombel_code' => 'IX-A', 'grade' => 9]);
    }

    public function test_demo_memiliki_materi_dan_kuis_published(): void
    {
        $this->assertDatabaseHas('learning_plans', [
            'topic' => 'Berpikir Komputasional: Dekomposisi Masalah',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('academic_years', ['code' => '2025-2026', 'is_active' => true]);
        $this->assertDatabaseHas('semesters', ['code' => 'ganjil', 'is_active' => true]);
        $this->assertDatabaseHas('subjects', ['code' => 'INF', 'name' => 'Informatika']);
        $this->assertDatabaseHas('curriculum_cps', ['element_code' => 'BK', 'phase' => 'D']);
        $this->assertDatabaseHas('learning_plans', [
            'topic' => 'Berpikir Komputasional: Dekomposisi Masalah',
            'curriculum_tp_id' => \App\Models\CurriculumTp::where('code', 'BK-VII-01')->value('id'),
        ]);
        $this->assertDatabaseHas('learning_materials', ['status' => 'published']);
        $this->assertDatabaseHas('quizzes', ['status' => 'published']);
    }

    public function test_halaman_login_bisa_diakses(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/Login'));
    }

    public function test_dashboard_butuh_auth(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_guru_bisa_akses_dashboard(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->first();
        $this->actingAs($guru)->get('/dashboard')->assertStatus(200);
    }

    public function test_homeroom_dashboard_bukan_guru(): void
    {
        $homeroom = User::where('email', 'arif@aksara.test')->firstOrFail();
        $this->actingAs($homeroom)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard/WaliKelas')
                ->has('userName')
                ->has('metrics')
                ->has('classes')
                ->missing('recentPlans'));
    }

    public function test_guru_dashboard_inertia(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $this->actingAs($guru)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard/Guru')
                ->has('metrics')
                ->has('recentPlans')
                ->has('urls'));
    }
}
