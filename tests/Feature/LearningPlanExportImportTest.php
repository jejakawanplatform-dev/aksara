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

use App\Models\LearningPlan;
use App\Models\User;
use App\Services\LearningPlanExportImportService;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LearningPlanExportImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
        $this->seed(SystemSettingSeeder::class);
    }

    public function test_guru_bisa_mengunduh_ekspor_excel_rencana_pembelajaran(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $response = $this->actingAs($guru)
            ->get(route('plans.export', ['format' => 'excel']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_guru_bisa_mengunduh_ekspor_word_rencana_pembelajaran(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $response = $this->actingAs($guru)
            ->get(route('plans.export', ['format' => 'word']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }

    public function test_guru_bisa_membuka_halaman_cetak_pdf_rencana_pembelajaran(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $response = $this->actingAs($guru)
            ->get(route('plans.export', ['format' => 'pdf']));

        $response->assertOk();
        $response->assertSee('Rekap Rencana Pembelajaran');
        $response->assertSee('NPSN');
        $response->assertSee((string) setting('school.name', 'SMP Negeri 1 Aksara'));
    }

    public function test_guru_bisa_mengunduh_ekspor_single_word_dan_pdf(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();

        $wordResponse = $this->actingAs($guru)
            ->get(route('plans.export.single', [$plan, 'word']));
        $wordResponse->assertOk();

        $excelResponse = $this->actingAs($guru)
            ->get(route('plans.export.single', [$plan, 'excel']));
        $excelResponse->assertOk();
        $excelResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $pdfResponse = $this->actingAs($guru)
            ->get(route('plans.export.single', [$plan, 'pdf']));
        $pdfResponse->assertOk();
        $pdfResponse->assertSee($plan->topic);
        $pdfResponse->assertSee('NPSN');
        $pdfResponse->assertSee((string) setting('school.name', 'SMP Negeri 1 Aksara'));
    }

    public function test_guru_bisa_mengunduh_template_impor_excel(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $response = $this->actingAs($guru)
            ->get(route('plans.import.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_guru_bisa_mengimpor_file_rencana_pembelajaran(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $service = app(LearningPlanExportImportService::class);

        $templateContent = $service->downloadTemplate();
        $file = UploadedFile::fake()->createWithContent('import.xlsx', $templateContent);

        $this->actingAs($guru)
            ->post(route('plans.import'), [
                'importFile' => $file,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('learning_plans', [
            'teacher_id' => $guru->id,
            'topic' => 'Berpikir Komputasional: Algoritma Pemrograman',
        ]);
    }
}
