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

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\CurriculumAtpItem;
use App\Models\CurriculumCp;
use App\Models\CurriculumTp;
use App\Models\Semester;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;

class CurriculumExportImportService
{
    /**
     * Generate Excel binary stream for CP & TP
     */
    public function exportCpTpExcel(Subject $subject): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('CP dan TP');

        // Header
        $sheet->setCellValue('A1', 'MATA PELAJARAN: '.strtoupper($subject->name));
        $sheet->setCellValue('A2', 'FASE: '.($subject->phase ?: 'D').' | JENJANG: '.($subject->jenjang ?: 'SMP'));

        $headers = ['No', 'Kode Elemen', 'Nama Elemen', 'Pernyataan Capaian Pembelajaran (CP)', 'Catatan Sumber', 'Kode TP', 'Kelas', 'Urutan TP', 'Pernyataan Tujuan Pembelajaran (TP)'];
        $sheet->fromArray($headers, null, 'A4');

        $row = 5;
        $no = 1;
        $cps = CurriculumCp::query()
            ->with(['tps' => fn ($q) => $q->orderBy('sequence')])
            ->where('subject_id', $subject->id)
            ->orderBy('sequence')
            ->get();

        foreach ($cps as $cp) {
            if ($cp->tps->isEmpty()) {
                $sheet->setCellValue("A{$row}", $no);
                $sheet->setCellValue("B{$row}", $cp->element_code);
                $sheet->setCellValue("C{$row}", $cp->element_name);
                $sheet->setCellValue("D{$row}", $cp->statement);
                $sheet->setCellValue("E{$row}", $cp->source_note ?: '');
                $sheet->setCellValue("F{$row}", '—');
                $sheet->setCellValue("G{$row}", '—');
                $sheet->setCellValue("H{$row}", '—');
                $sheet->setCellValue("I{$row}", '—');
                $row++;
            } else {
                foreach ($cp->tps as $tp) {
                    $sheet->setCellValue("A{$row}", $no);
                    $sheet->setCellValue("B{$row}", $cp->element_code);
                    $sheet->setCellValue("C{$row}", $cp->element_name);
                    $sheet->setCellValue("D{$row}", $cp->statement);
                    $sheet->setCellValue("E{$row}", $cp->source_note ?: '');
                    $sheet->setCellValue("F{$row}", $tp->code);
                    $sheet->setCellValue("G{$row}", $tp->grade ?: '—');
                    $sheet->setCellValue("H{$row}", $tp->sequence);
                    $sheet->setCellValue("I{$row}", $tp->statement);
                    $row++;
                }
            }
            $no++;
        }

        foreach (range('A', 'I') as $col) {
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
     * Generate Word binary stream for CP & TP (.docx)
     */
    public function exportCpTpWord(Subject $subject): string
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection([
            'marginTop' => 1440,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);

        $section->addText('CAPAIAN PEMBELAJARAN (CP) DAN TUJUAN PEMBELAJARAN (TP)', ['bold' => true, 'size' => 16, 'color' => '0F766E']);
        $section->addText('Mata Pelajaran: '.$subject->name.' ('.$subject->code.')', ['bold' => true, 'size' => 11]);
        $section->addText('Fase: '.($subject->phase ?: 'D').' | Jenjang: '.($subject->jenjang ?: 'SMP'), ['size' => 10, 'color' => '475569']);
        $section->addTextBreak(1);

        $cps = CurriculumCp::query()
            ->with(['tps' => fn ($q) => $q->orderBy('sequence')])
            ->where('subject_id', $subject->id)
            ->orderBy('sequence')
            ->get();

        foreach ($cps as $index => $cp) {
            $section->addText(($index + 1).'. ELEMEN: '.$cp->element_name.' ('.$cp->element_code.')', ['bold' => true, 'size' => 12, 'color' => '0D9488']);
            $section->addText('Pernyataan CP: '.$cp->statement, ['italic' => true, 'size' => 10]);
            if ($cp->source_note) {
                $section->addText('Sumber: '.$cp->source_note, ['size' => 9, 'color' => '64748B']);
            }

            $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CBD5E1', 'cellMargin' => 100]);
            $table->addRow();
            $table->addCell(1500, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('Kode TP', ['bold' => true, 'size' => 10]);
            $table->addCell(1000, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('Kelas', ['bold' => true, 'size' => 10]);
            $table->addCell(6500, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('Pernyataan Tujuan Pembelajaran (TP)', ['bold' => true, 'size' => 10]);

            foreach ($cp->tps as $tp) {
                $table->addRow();
                $table->addCell(1500, ['noWrap' => false])->addText((string) $tp->code, ['size' => 10]);
                $table->addCell(1000, ['noWrap' => false])->addText((string) ($tp->grade ?: '—'), ['size' => 10]);
                $table->addCell(6500, ['noWrap' => false])->addText((string) $tp->statement, ['size' => 10]);
            }
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
     * Generate Excel binary stream for ATP
     */
    public function exportAtpExcel(Subject $subject, ?int $grade = null): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('ATP');

        $sheet->setCellValue('A1', 'ALUR TUJUAN PEMBELAJARAN (ATP) - '.strtoupper($subject->name));
        $sheet->setCellValue('A2', 'FASE: '.($subject->phase ?: 'D').($grade ? " | KELAS: {$grade}" : ' | SEMUA KELAS'));

        $headers = ['No Urut', 'Kelas', 'Semester', 'Judul Unit / Modul', 'Kode TP', 'Pernyataan Tujuan Pembelajaran (TP)', 'Estimasi JP'];
        $sheet->fromArray($headers, null, 'A4');

        $atpItems = CurriculumAtpItem::query()
            ->with(['tp', 'semester'])
            ->where('subject_id', $subject->id)
            ->when($grade, fn ($q) => $q->where('grade', $grade))
            ->orderBy('grade')
            ->orderBy('sequence')
            ->get();

        $row = 5;
        foreach ($atpItems as $item) {
            $sheet->setCellValue("A{$row}", $item->sequence);
            $sheet->setCellValue("B{$row}", $item->grade);
            $sheet->setCellValue("C{$row}", $item->semester?->name ?: 'Semua');
            $sheet->setCellValue("D{$row}", $item->unit_title ?: '—');
            $sheet->setCellValue("E{$row}", $item->tp?->code ?: '—');
            $sheet->setCellValue("F{$row}", $item->tp?->statement ?: '—');
            $sheet->setCellValue("G{$row}", $item->estimated_meetings ?: '—');
            $row++;
        }

        foreach (range('A', 'G') as $col) {
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
     * Generate Word binary stream for ATP (.docx)
     */
    public function exportAtpWord(Subject $subject, ?int $grade = null): string
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection([
            'marginTop' => 1440,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);

        $section->addText('ALUR TUJUAN PEMBELAJARAN (ATP)', ['bold' => true, 'size' => 16, 'color' => '0F766E']);
        $section->addText('Mata Pelajaran: '.$subject->name.' ('.$subject->code.')', ['bold' => true, 'size' => 11]);
        $section->addText('Fase: '.($subject->phase ?: 'D').($grade ? ' | Kelas: '.$grade : ' | Semua Kelas'), ['size' => 10, 'color' => '475569']);
        $section->addTextBreak(1);

        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CBD5E1', 'cellMargin' => 100]);
        $table->addRow();
        $table->addCell(800, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('No', ['bold' => true, 'size' => 10]);
        $table->addCell(800, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('Kelas', ['bold' => true, 'size' => 10]);
        $table->addCell(1200, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('Semester', ['bold' => true, 'size' => 10]);
        $table->addCell(2000, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('Judul Unit', ['bold' => true, 'size' => 10]);
        $table->addCell(1500, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('Kode TP', ['bold' => true, 'size' => 10]);
        $table->addCell(4000, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('Pernyataan TP', ['bold' => true, 'size' => 10]);
        $table->addCell(800, ['noWrap' => false, 'bgColor' => 'F1F5F9'])->addText('JP', ['bold' => true, 'size' => 10]);

        $atpItems = CurriculumAtpItem::query()
            ->with(['tp', 'semester'])
            ->where('subject_id', $subject->id)
            ->when($grade, fn ($q) => $q->where('grade', $grade))
            ->orderBy('grade')
            ->orderBy('sequence')
            ->get();

        foreach ($atpItems as $item) {
            $table->addRow();
            $table->addCell(800, ['noWrap' => false])->addText((string) $item->sequence, ['size' => 10]);
            $table->addCell(800, ['noWrap' => false])->addText((string) $item->grade, ['size' => 10]);
            $table->addCell(1200, ['noWrap' => false])->addText((string) ($item->semester?->name ?: '—'), ['size' => 10]);
            $table->addCell(2000, ['noWrap' => false])->addText((string) ($item->unit_title ?: '—'), ['size' => 10]);
            $table->addCell(1500, ['noWrap' => false])->addText((string) ($item->tp?->code ?: '—'), ['size' => 10]);
            $table->addCell(4000, ['noWrap' => false])->addText((string) ($item->tp?->statement ?: '—'), ['size' => 10]);
            $table->addCell(800, ['noWrap' => false])->addText((string) ($item->estimated_meetings ?: '—'), ['size' => 10]);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
        $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        $content = file_get_contents($tempFile) ?: '';
        @unlink($tempFile);

        return $content;
    }

    /**
     * Import CP & TP from uploaded file (Excel/CSV)
     */
    public function importCpTp(string $filePath, int $subjectId): int
    {
        $spreadsheet = SpreadsheetIOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        if (count($rows) < 2) {
            return 0;
        }

        $importedCount = 0;
        DB::transaction(function () use ($rows, $subjectId, &$importedCount) {
            // Find start row (header contains "Kode Elemen" or row >= 4)
            $startRowIndex = 1;
            foreach ($rows as $idx => $r) {
                if (isset($r[1]) && (str_contains(strtolower((string) $r[1]), 'elemen') || str_contains(strtolower((string) $r[1]), 'kode'))) {
                    $startRowIndex = $idx + 1;
                    break;
                }
            }

            for ($i = $startRowIndex; $i < count($rows); $i++) {
                $row = $rows[$i];
                $elemCode = trim((string) ($row[1] ?? ''));
                $elemName = trim((string) ($row[2] ?? ''));
                $cpStatement = trim((string) ($row[3] ?? ''));
                $sourceNote = trim((string) ($row[4] ?? ''));
                $tpCode = trim((string) ($row[5] ?? ''));
                $tpGrade = (int) ($row[6] ?? 7);
                $tpSeq = (int) ($row[7] ?? 1);
                $tpStatement = trim((string) ($row[8] ?? ''));

                if (! $elemCode || ! $cpStatement) {
                    continue;
                }

                $cp = CurriculumCp::firstOrCreate(
                    [
                        'subject_id' => $subjectId,
                        'element_code' => strtoupper($elemCode),
                    ],
                    [
                        'phase' => 'D',
                        'element_name' => $elemName ?: $elemCode,
                        'statement' => $cpStatement,
                        'source_note' => $sourceNote ?: null,
                        'sequence' => (int) (CurriculumCp::where('subject_id', $subjectId)->max('sequence') ?? 0) + 1,
                    ]
                );

                if ($tpCode && $tpStatement) {
                    CurriculumTp::updateOrCreate(
                        [
                            'curriculum_cp_id' => $cp->id,
                            'code' => $tpCode,
                        ],
                        [
                            'statement' => $tpStatement,
                            'grade' => $tpGrade ?: 7,
                            'sequence' => $tpSeq ?: 1,
                        ]
                    );
                    $importedCount++;
                }
            }
        });

        return $importedCount;
    }

    /**
     * Import ATP items from uploaded file (Excel/CSV)
     */
    public function importAtp(string $filePath, int $subjectId): int
    {
        $spreadsheet = SpreadsheetIOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        if (count($rows) < 2) {
            return 0;
        }

        $activeYear = AcademicYear::active() ?? AcademicYear::first();
        if (! $activeYear) {
            return 0;
        }

        $importedCount = 0;
        DB::transaction(function () use ($rows, $subjectId, $activeYear, &$importedCount) {
            $startRowIndex = 1;
            foreach ($rows as $idx => $r) {
                if (isset($r[4]) && (str_contains(strtolower((string) $r[4]), 'kode') || str_contains(strtolower((string) $r[4]), 'tp'))) {
                    $startRowIndex = $idx + 1;
                    break;
                }
            }

            for ($i = $startRowIndex; $i < count($rows); $i++) {
                $row = $rows[$i];
                $seq = (int) ($row[0] ?? ($i - $startRowIndex + 1));
                $grade = (int) ($row[1] ?? 7);
                $semName = trim((string) ($row[2] ?? ''));
                $unitTitle = trim((string) ($row[3] ?? ''));
                $tpCode = trim((string) ($row[4] ?? ''));
                $estimated = (int) ($row[6] ?? 2);

                if (! $tpCode) {
                    continue;
                }

                $tp = CurriculumTp::where('code', $tpCode)
                    ->whereHas('cp', fn ($cq) => $cq->where('subject_id', $subjectId))
                    ->first();

                if (! $tp) {
                    continue;
                }

                $semesterId = null;
                if ($semName) {
                    $semesterId = Semester::where('academic_year_id', $activeYear->id)
                        ->where('name', 'like', "%{$semName}%")
                        ->value('id');
                }

                CurriculumAtpItem::updateOrCreate(
                    [
                        'subject_id' => $subjectId,
                        'academic_year_id' => $activeYear->id,
                        'curriculum_tp_id' => $tp->id,
                    ],
                    [
                        'semester_id' => $semesterId,
                        'grade' => $grade ?: 7,
                        'sequence' => $seq ?: 1,
                        'unit_title' => $unitTitle ?: null,
                        'estimated_meetings' => $estimated ?: 2,
                    ]
                );
                $importedCount++;
            }
        });

        return $importedCount;
    }
}
