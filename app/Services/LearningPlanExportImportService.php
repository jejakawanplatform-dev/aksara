<?php

namespace App\Services;

use App\Enums\PlanStatus;
use App\Models\AcademicYear;
use App\Models\LearningPlan;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;

class LearningPlanExportImportService
{
    /**
     * Export multiple Learning Plans to Excel (.xlsx)
     */
    public function exportPlansExcel(Collection $plans): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rencana Pembelajaran');

        // Header Metadata
        $sheet->setCellValue('A1', 'REKAP RENCANA PEMBELAJARAN (MODUL AJAR)');
        $sheet->setCellValue('A2', 'Dibuat pada: ' . now()->translatedFormat('d F Y H:i'));

        $headers = [
            'No',
            'Topik Pembelajaran',
            'Mata Pelajaran',
            'Kode Mapel',
            'Kelas / Rombel',
            'Tingkat',
            'Fase',
            'Durasi (Menit)',
            'Tujuan Pembelajaran',
            'Referensi Kurikulum',
            'Status',
            'Guru Pengampu',
            'Tahun Ajaran',
            'Semester',
        ];
        $sheet->fromArray($headers, null, 'A4');

        $row = 5;
        $no = 1;
        foreach ($plans as $plan) {
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $plan->topic);
            $sheet->setCellValue("C{$row}", $plan->subject->name ?? '—');
            $sheet->setCellValue("D{$row}", $plan->subject->code ?? '—');
            $sheet->setCellValue("E{$row}", $plan->class->name ?? '—');
            $sheet->setCellValue("F{$row}", $plan->grade);
            $sheet->setCellValue("G{$row}", $plan->phase);
            $sheet->setCellValue("H{$row}", $plan->duration_minutes);
            $sheet->setCellValue("I{$row}", $plan->learning_objectives);
            $sheet->setCellValue("J{$row}", $plan->curriculum_reference);
            $sheet->setCellValue("K{$row}", $plan->status->label());
            $sheet->setCellValue("L{$row}", $plan->teacher->name ?? '—');
            $sheet->setCellValue("M{$row}", $plan->academicYear->name ?? '—');
            $sheet->setCellValue("N{$row}", $plan->semester->name ?? '—');
            $row++;
        }

        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer = new XlsxWriter($spreadsheet);
        $writer->save($tempFile);

        $content = file_get_contents($tempFile) ?: '';
        @unlink($tempFile);

        return $content;
    }

    /**
     * Export multiple Learning Plans to Word (.docx)
     */
    public function exportPlansWord(Collection $plans, ?string $title = null): string
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection([
            'marginTop' => 1440,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);

        $docTitle = $title ?: 'DAFTAR RENCANA PEMBELAJARAN (MODUL AJAR)';
        $section->addText($docTitle, ['bold' => true, 'size' => 16, 'color' => '0F766E']);
        $section->addText('Tanggal Cetak: ' . now()->translatedFormat('d F Y H:i'), ['size' => 10, 'color' => '475569']);
        $section->addTextBreak(1);

        foreach ($plans as $index => $plan) {
            $section->addText(($index + 1) . '. TOPIK: ' . mb_strtoupper($plan->topic), ['bold' => true, 'size' => 12, 'color' => '0D9488']);
            
            $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CBD5E1', 'cellMargin' => 100]);
            
            $table->addRow();
            $table->addCell(2500, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('Mata Pelajaran', ['bold' => true, 'size' => 9]);
            $table->addCell(6500, ['noWrap' => false])->addText(($plan->subject->name ?? '—') . ' (' . ($plan->subject->code ?? '—') . ')', ['size' => 9]);

            $table->addRow();
            $table->addCell(2500, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('Kelas / Fase / Durasi', ['bold' => true, 'size' => 9]);
            $table->addCell(6500, ['noWrap' => false])->addText('Kelas ' . ($plan->class->name ?? $plan->grade) . ' | Fase ' . $plan->phase . ' | ' . $plan->duration_minutes . ' menit', ['size' => 9]);

            $table->addRow();
            $table->addCell(2500, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('Guru / Status', ['bold' => true, 'size' => 9]);
            $table->addCell(6500, ['noWrap' => false])->addText(($plan->teacher->name ?? '—') . ' (' . $plan->status->label() . ')', ['size' => 9]);

            $table->addRow();
            $table->addCell(2500, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('Tujuan Pembelajaran', ['bold' => true, 'size' => 9]);
            $table->addCell(6500, ['noWrap' => false])->addText((string) $plan->learning_objectives, ['size' => 9]);

            $table->addRow();
            $table->addCell(2500, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('Referensi Kurikulum', ['bold' => true, 'size' => 9]);
            $table->addCell(6500, ['noWrap' => false])->addText((string) $plan->curriculum_reference, ['size' => 9]);

            $section->addTextBreak(1);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
        $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        $content = file_get_contents($tempFile) ?: '';
        @unlink($tempFile);

        return $content;
    }

    /**
     * Export single Learning Plan to Word (.docx)
     */
    public function exportSinglePlanWord(LearningPlan $plan): string
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection([
            'marginTop' => 1440,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);

        // Header Title
        $section->addText('MODUL AJAR / RENCANA PEMBELAJARAN', ['bold' => true, 'size' => 16, 'color' => '0F766E'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $section->addText(mb_strtoupper($plan->topic), ['bold' => true, 'size' => 14, 'color' => '0D9488'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $section->addTextBreak(1);

        // Identitas Modul Table
        $section->addText('I. IDENTITAS MODUL', ['bold' => true, 'size' => 11, 'color' => '0F766E']);
        $table1 = $section->addTable(['borderSize' => 6, 'borderColor' => 'CBD5E1', 'cellMargin' => 100]);

        $rows = [
            ['Mata Pelajaran', ($plan->subject->name ?? '—') . ' (' . ($plan->subject->code ?? '—') . ')'],
            ['Guru Pengampu', $plan->teacher->name ?? '—'],
            ['Kelas / Fase', 'Kelas ' . ($plan->class->name ?? $plan->grade) . ' / Fase ' . $plan->phase],
            ['Tahun Ajaran / Semester', ($plan->academicYear->name ?? '—') . ' / ' . ($plan->semester->name ?? '—')],
            ['Alokasi Waktu', $plan->duration_minutes . ' Menit'],
            ['Status Modul', $plan->status->label()],
        ];

        foreach ($rows as $r) {
            $table1->addRow();
            $table1->addCell(3000, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText($r[0], ['bold' => true, 'size' => 10]);
            $table1->addCell(6000, ['noWrap' => false])->addText($r[1], ['size' => 10]);
        }

        $section->addTextBreak(1);

        // Komponen Inti
        $section->addText('II. KOMPONEN INTI', ['bold' => true, 'size' => 11, 'color' => '0F766E']);
        $table2 = $section->addTable(['borderSize' => 6, 'borderColor' => 'CBD5E1', 'cellMargin' => 100]);

        $table2->addRow();
        $table2->addCell(3000, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('Tujuan Pembelajaran (TP)', ['bold' => true, 'size' => 10]);
        $table2->addCell(6000, ['noWrap' => false])->addText((string) $plan->learning_objectives, ['size' => 10]);

        $table2->addRow();
        $table2->addCell(3000, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('Referensi Kurikulum (CP/TP)', ['bold' => true, 'size' => 10]);
        $table2->addCell(6000, ['noWrap' => false])->addText((string) $plan->curriculum_reference, ['size' => 10]);

        if ($plan->student_needs) {
            $table2->addRow();
            $table2->addCell(3000, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('Kebutuhan / Catatan Khusus', ['bold' => true, 'size' => 10]);
            $table2->addCell(6000, ['noWrap' => false])->addText((string) $plan->student_needs, ['size' => 10]);
        }

        $section->addTextBreak(1);

        // Material Summary if exists
        if ($plan->material) {
            $section->addText('III. MATERI PEMBELAJARAN', ['bold' => true, 'size' => 11, 'color' => '0F766E']);
            $mat = $plan->material;
            $content = $mat->content ?? [];

            $section->addText('Judul Materi: ' . ($content['title'] ?? $plan->topic), ['bold' => true, 'size' => 10]);

            if (!empty($content['sections']) && is_array($content['sections'])) {
                foreach ($content['sections'] as $sec) {
                    if (!empty($sec['heading'])) {
                        $heading = is_array($sec['heading']) ? implode(' ', array_map('strval', $sec['heading'])) : (string) $sec['heading'];
                        $section->addText($heading, ['bold' => true, 'size' => 10, 'color' => '0D9488']);
                    }
                    if (!empty($sec['body'])) {
                        $body = is_array($sec['body']) ? implode("\n", array_map('strval', $sec['body'])) : (string) $sec['body'];
                        $section->addText(strip_tags($body), ['size' => 10]);
                    }
                }
            }

            if (!empty($content['reflectionQuestion'])) {
                $refQ = is_array($content['reflectionQuestion']) 
                    ? implode('; ', array_map('strval', $content['reflectionQuestion'])) 
                    : (string) $content['reflectionQuestion'];

                $section->addTextBreak(1);
                $section->addText('Pertanyaan Refleksi:', ['bold' => true, 'size' => 10]);
                $section->addText($refQ, ['italic' => true, 'size' => 10]);
            }
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
        $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        $content = file_get_contents($tempFile) ?: '';
        @unlink($tempFile);

        return $content;
    }

    /**
     * Download Excel import template for Learning Plans
     */
    public function downloadTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Modul Ajar');

        // Instructions
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT RENCANA PEMBELAJARAN (MODUL AJAR)');
        $sheet->setCellValue('A2', 'Isi data sesuai kolom berikut. Kolom dengan tanda (*) wajib diisi.');

        $headers = [
            'Topik Pembelajaran (*)',
            'Kode Mapel (*)',
            'Tingkat Kelas (7-9/10-12)',
            'Fase (A-F)',
            'Durasi Menit',
            'Tujuan Pembelajaran (*)',
            'Referensi Kurikulum',
            'Catatan Kebutuhan Belajar',
        ];
        $sheet->fromArray($headers, null, 'A4');

        // Example Rows
        $examples = [
            [
                'Berpikir Komputasional: Algoritma Pemrograman',
                'INF',
                '7',
                'D',
                '80',
                'Siswa mampu merancang algoritma sederhana untuk menyelesaikan masalah sehari-hari.',
                'CP INF — TP BK-01',
                'Visual learner / butuh pendampingan khusus',
            ],
            [
                'Jaringan Komputer dan Internet',
                'INF',
                '8',
                'D',
                '80',
                'Siswa memahami konsep dasar topologi jaringan dan komunikasi data.',
                'CP INF — TP JKI-01',
                'Praktikum komputer mandiri',
            ],
        ];

        $sheet->fromArray($examples, null, 'A5');

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer = new XlsxWriter($spreadsheet);
        $writer->save($tempFile);

        $content = file_get_contents($tempFile) ?: '';
        @unlink($tempFile);

        return $content;
    }

    /**
     * Import Learning Plans from Excel or CSV file
     */
    public function importPlans(UploadedFile $file, int $teacherId): array
    {
        $spreadsheet = SpreadsheetIOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        if (count($rows) < 5) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['File tidak memiliki baris data yang cukup.'],
            ];
        }

        // Find header row (usually row 4 or first row with Topik/Topic)
        $headerIndex = -1;
        foreach ($rows as $idx => $r) {
            $str = implode(' ', array_filter($r));
            if (stripos($str, 'Topik') !== false || stripos($str, 'Kode Mapel') !== false) {
                $headerIndex = $idx;
                break;
            }
        }

        if ($headerIndex === -1) {
            $headerIndex = 3; // default row 4
        }

        // Resolve active Academic Year & Semester
        $activeYear = AcademicYear::active() ?? AcademicYear::query()->first();
        $activeSemester = Semester::active() ?? Semester::query()->first();

        if (!$activeYear || !$activeSemester) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['Tahun Ajaran atau Semester aktif belum dikonfigurasi.'],
            ];
        }

        $importedCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            for ($i = $headerIndex + 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $topic = trim((string) ($row[0] ?? ''));

                if (empty($topic)) {
                    continue; // Skip empty rows
                }

                $subjectCode = trim((string) ($row[1] ?? 'INF'));
                $grade = (int) ($row[2] ?? 7);
                $phase = trim((string) ($row[3] ?? 'D')) ?: 'D';
                $duration = (int) ($row[4] ?? 80) ?: 80;
                $objectives = trim((string) ($row[5] ?? ''));
                $ref = trim((string) ($row[6] ?? ''));
                $needs = trim((string) ($row[7] ?? ''));

                if (empty($objectives)) {
                    $errors[] = "Baris " . ($i + 1) . ": Tujuan Pembelajaran wajib diisi.";
                    continue;
                }

                // Match Subject by code or name
                $subject = Subject::query()
                    ->where('code', $subjectCode)
                    ->orWhere('name', 'like', "%{$subjectCode}%")
                    ->first();

                if (!$subject) {
                    $subject = Subject::query()->first();
                }

                if (!$subject) {
                    $errors[] = "Baris " . ($i + 1) . ": Mata pelajaran '{$subjectCode}' tidak ditemukan.";
                    continue;
                }

                // Match Class by grade
                $class = SchoolClass::query()
                    ->where('grade', $grade)
                    ->first();

                LearningPlan::create([
                    'teacher_id' => $teacherId,
                    'academic_year_id' => $activeYear->id,
                    'semester_id' => $activeSemester->id,
                    'class_id' => $class?->id ?? SchoolClass::query()->value('id') ?? 1,
                    'subject_id' => $subject->id,
                    'phase' => strtoupper($phase),
                    'grade' => $grade ?: 7,
                    'topic' => $topic,
                    'duration_minutes' => $duration,
                    'learning_objectives' => $objectives,
                    'curriculum_reference' => $ref ?: "Ref: {$subject->name} - {$topic}",
                    'student_needs' => $needs ?: null,
                    'status' => PlanStatus::Draft,
                ]);

                $importedCount++;
            }

            DB::commit();

            return [
                'success' => true,
                'imported' => $importedCount,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['Gagal memproses impor file: ' . $e->getMessage()],
            ];
        }
    }
}
