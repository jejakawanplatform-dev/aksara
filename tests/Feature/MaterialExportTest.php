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

use App\Enums\MaterialStatus;
use App\Models\LearningEvent;
use App\Models\LearningMaterial;
use App\Models\LearningPlan;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Services\MaterialExportService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_guest_dilarang_mengunduh_materi(): void
    {
        $material = LearningMaterial::where('status', MaterialStatus::Published)->firstOrFail();

        $this->get(route('materials.export.single', [$material, 'pdf']))
            ->assertRedirect(route('login'));

        $this->get(route('materials.export.single', [$material, 'word']))
            ->assertRedirect(route('login'));

        $this->get(route('materials.export.single', [$material, 'markdown']))
            ->assertRedirect(route('login'));
    }

    public function test_siswa_dapat_mengunduh_materi_terbit_kelasnya_format_pdf(): void
    {
        $siswa = User::where('email', 'adit@aksara.test')->firstOrFail();
        $material = LearningMaterial::where('status', MaterialStatus::Published)->firstOrFail();

        $response = $this->actingAs($siswa)
            ->get(route('materials.export.single', [$material, 'pdf']));

        $response->assertOk();
        $response->assertViewIs('exports.material-pdf');
        $response->assertSee('Bahan Ajar / Materi Pembelajaran');
        $response->assertSee('Dekomposisi');

        $this->assertDatabaseHas('learning_events', [
            'material_id' => $material->id,
            'student_id' => $siswa->id,
            'event_type' => 'material_opened',
        ]);
    }

    public function test_siswa_dapat_mengunduh_materi_terbit_kelasnya_format_word(): void
    {
        $siswa = User::where('email', 'adit@aksara.test')->firstOrFail();
        $material = LearningMaterial::where('status', MaterialStatus::Published)->firstOrFail();

        $response = $this->actingAs($siswa)
            ->get(route('materials.export.single', [$material, 'word']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('attachment;', $disposition);
        $this->assertStringContainsString('.docx', $disposition);
    }

    public function test_siswa_dapat_mengunduh_materi_terbit_kelasnya_format_markdown(): void
    {
        $siswa = User::where('email', 'adit@aksara.test')->firstOrFail();
        $material = LearningMaterial::where('status', MaterialStatus::Published)->firstOrFail();

        $response = $this->actingAs($siswa)
            ->get(route('materials.export.single', [$material, 'markdown']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/markdown; charset=UTF-8');
        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('attachment;', $disposition);
        $this->assertStringContainsString('.md', $disposition);
    }

    public function test_siswa_dilarang_mengunduh_materi_draf(): void
    {
        $siswa = User::where('email', 'adit@aksara.test')->firstOrFail();
        $published = LearningMaterial::where('status', MaterialStatus::Published)->firstOrFail();

        // Buat materi draf di kelas yang sama
        $draft = LearningMaterial::create([
            'plan_id' => $published->plan_id,
            'status' => MaterialStatus::Draft,
            'content' => [
                'title' => 'Materi Draf Rahasia',
                'sections' => [['heading' => 'Draf', 'body' => '<p>Belum siap baca.</p>']],
            ],
        ]);

        $this->actingAs($siswa)
            ->get(route('materials.export.single', [$draft, 'pdf']))
            ->assertForbidden();

        $this->actingAs($siswa)
            ->get(route('materials.export.single', [$draft, 'word']))
            ->assertForbidden();

        $this->actingAs($siswa)
            ->get(route('materials.export.single', [$draft, 'markdown']))
            ->assertForbidden();
    }

    public function test_siswa_dilarang_mengunduh_materi_kelas_lain(): void
    {
        $siswa = User::where('email', 'adit@aksara.test')->firstOrFail();
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        // Buat kelas lain di mana siswa bukan anggotanya
        $otherClass = SchoolClass::create([
            'name' => 'VII-Z',
            'grade' => 7,
            'rombel_code' => 'VII-Z',
        ]);
        $subject = Subject::firstOrFail();

        $otherPlan = LearningPlan::factory()->create([
            'teacher_id' => $guru->id,
            'class_id' => $otherClass->id,
            'subject_id' => $subject->id,
            'topic' => 'Materi Kelas Lain',
        ]);

        $otherMaterial = LearningMaterial::create([
            'plan_id' => $otherPlan->id,
            'status' => MaterialStatus::Published,
            'content' => [
                'title' => 'Materi Khusus Kelas 9-Z',
                'sections' => [['heading' => 'Topik', 'body' => '<p>Konten rahasia kelas lain.</p>']],
            ],
        ]);

        $this->actingAs($siswa)
            ->get(route('materials.export.single', [$otherMaterial, 'pdf']))
            ->assertForbidden();

        $this->actingAs($siswa)
            ->get(route('materials.export.single', [$otherMaterial, 'word']))
            ->assertForbidden();
    }

    public function test_guru_pemilik_dapat_mengunduh_materi_draf_dan_terbit(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $material = LearningMaterial::where('status', MaterialStatus::Published)->firstOrFail();

        // Download published
        $this->actingAs($guru)
            ->get(route('materials.export.single', [$material, 'pdf']))
            ->assertOk()
            ->assertViewIs('exports.material-pdf');

        $this->actingAs($guru)
            ->get(route('materials.export.single', [$material, 'word']))
            ->assertOk();

        $this->actingAs($guru)
            ->get(route('materials.export.single', [$material, 'markdown']))
            ->assertOk();

        // Download draft
        $draft = LearningMaterial::create([
            'plan_id' => $material->plan_id,
            'status' => MaterialStatus::Draft,
            'content' => [
                'title' => 'Materi Draf Guru',
                'sections' => [['heading' => 'Draf Seksi', 'body' => '<p>Masih draf.</p>']],
            ],
        ]);

        $this->actingAs($guru)
            ->get(route('materials.export.single', [$draft, 'pdf']))
            ->assertOk();

        $this->actingAs($guru)
            ->get(route('materials.export.single', [$draft, 'word']))
            ->assertOk();
    }

    public function test_guru_lain_dilarang_mengunduh_materi_milik_guru_lain(): void
    {
        $otherTeacher = User::factory()->create(['name' => 'Guru Lain']);
        $otherTeacher->assignRole('teacher');

        $material = LearningMaterial::where('status', MaterialStatus::Published)->firstOrFail();

        $this->actingAs($otherTeacher)
            ->get(route('materials.export.single', [$material, 'pdf']))
            ->assertForbidden();

        $this->actingAs($otherTeacher)
            ->get(route('materials.export.single', [$material, 'word']))
            ->assertForbidden();

        $this->actingAs($otherTeacher)
            ->get(route('materials.export.single', [$material, 'markdown']))
            ->assertForbidden();
    }

    public function test_admin_dapat_mengunduh_semua_materi(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $material = LearningMaterial::where('status', MaterialStatus::Published)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('materials.export.single', [$material, 'pdf']))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('materials.export.single', [$material, 'word']))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('materials.export.single', [$material, 'markdown']))
            ->assertOk();
    }

    public function test_format_ekspor_tidak_valid_mengembalikan_404(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $material = LearningMaterial::where('status', MaterialStatus::Published)->firstOrFail();

        $this->actingAs($guru)
            ->get(route('materials.export.single', [$material, 'excel']))
            ->assertNotFound();

        $this->actingAs($guru)
            ->get(route('materials.export.single', [$material, 'zip']))
            ->assertNotFound();
    }

    public function test_service_ekspor_menghasilkan_output_valid(): void
    {
        /** @var MaterialExportService $service */
        $service = app(MaterialExportService::class);
        $material = LearningMaterial::where('status', MaterialStatus::Published)->firstOrFail();

        // 1. Data ekstraksi
        $data = $service->extractMaterialData($material);
        $this->assertNotEmpty($data['title']);
        $this->assertIsArray($data['sections']);
        $this->assertIsArray($data['reflections']);
        $this->assertNotEmpty($data['plan']['subject']);

        // 2. Word binary
        $wordOutput = $service->exportWord($material);
        $this->assertNotEmpty($wordOutput);
        // Header Zip/Docx dimulai dengan "PK"
        $this->assertStringStartsWith('PK', $wordOutput);

        // 3. Markdown text
        $mdOutput = $service->exportMarkdown($material);
        $this->assertStringContainsString('---', $mdOutput);
        $this->assertStringContainsString('title:', $mdOutput);
        $this->assertStringContainsString('# '.$data['title'], $mdOutput);

        // 4. Prepare print data
        $printData = $service->preparePrintData($material);
        $this->assertArrayHasKey('title', $printData);
        $this->assertArrayHasKey('sections', $printData);
        $this->assertArrayHasKey('reflections', $printData);
        $this->assertArrayHasKey('plan', $printData);
    }
}
