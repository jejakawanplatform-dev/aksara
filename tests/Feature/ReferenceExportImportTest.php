<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use App\Services\CurriculumExportImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReferenceExportImportTest extends \Tests\TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoDataSeeder::class);
    }

    public function test_export_cp_tp_excel_and_word(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $inf = Subject::where('code', 'INF')->firstOrFail();

        $responseExcel = $this->actingAs($admin)
            ->get(route('references.export.cp-tp', ['subject' => $inf->id, 'format' => 'excel']));

        $responseExcel->assertStatus(200);
        $responseExcel->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $responseWord = $this->actingAs($admin)
            ->get(route('references.export.cp-tp', ['subject' => $inf->id, 'format' => 'word']));

        $responseWord->assertStatus(200);
        $responseWord->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $responsePdf = $this->actingAs($admin)
            ->get(route('references.export.cp-tp', ['subject' => $inf->id, 'format' => 'pdf']));

        $responsePdf->assertStatus(200);
        $responsePdf->assertSee('Dokumen Capaian Pembelajaran (CP)');
    }

    public function test_export_atp_excel_word_and_pdf(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $inf = Subject::where('code', 'INF')->firstOrFail();

        $responseExcel = $this->actingAs($admin)
            ->get(route('references.export.atp', ['subject' => $inf->id, 'format' => 'excel']));

        $responseExcel->assertStatus(200);
        $responseExcel->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $responseWord = $this->actingAs($admin)
            ->get(route('references.export.atp', ['subject' => $inf->id, 'format' => 'word']));

        $responseWord->assertStatus(200);

        $responsePdf = $this->actingAs($admin)
            ->get(route('references.export.atp', ['subject' => $inf->id, 'format' => 'pdf']));

        $responsePdf->assertStatus(200);
        $responsePdf->assertSee('Alur Tujuan Pembelajaran (ATP)');
    }

    public function test_service_export_stream_methods(): void
    {
        $inf = Subject::where('code', 'INF')->firstOrFail();
        /** @var CurriculumExportImportService $service */
        $service = app(CurriculumExportImportService::class);

        $excelCpStream = $service->exportCpTpExcel($inf);
        $this->assertNotEmpty($excelCpStream);

        $wordCpStream = $service->exportCpTpWord($inf);
        $this->assertNotEmpty($wordCpStream);

        $excelAtpStream = $service->exportAtpExcel($inf);
        $this->assertNotEmpty($excelAtpStream);

        $wordAtpStream = $service->exportAtpWord($inf);
        $this->assertNotEmpty($wordAtpStream);
    }
}
