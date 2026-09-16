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

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\LearningPlan;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class AttendanceExportService
{
    /**
     * Membangun baris rekap kehadiran untuk setiap siswa pada rombel kelas.
     *
     * @param  Collection<int, int>  $planIds
     * @return list<array{studentId: int, studentName: string, studentEmail: string, hadir: int, izin: int, sakit: int, alpha: int, total: int, pct: int, status: string}>
     */
    public function buildSummaryRows(SchoolClass $class, Collection $planIds): array
    {
        $students = $class->students()->orderBy('name')->get();

        if ($planIds->isEmpty() || $students->isEmpty()) {
            return [];
        }

        /** @var list<array{studentId: int, studentName: string, studentEmail: string, hadir: int, izin: int, sakit: int, alpha: int, total: int, pct: int, status: string}> $rows */
        $rows = [];

        foreach ($students as $student) {
            $records = AttendanceRecord::query()
                ->where('student_id', $student->id)
                ->whereIn('plan_id', $planIds)
                ->get();

            $hadir = $records->where('status', AttendanceStatus::Present)->count();
            $izin = $records->where('status', AttendanceStatus::Excused)->count();
            $sakit = $records->where('status', AttendanceStatus::Sick)->count();
            $alpha = $records->where('status', AttendanceStatus::Absent)->count();
            $total = $records->count();

            $pct = $total > 0 ? (int) round(($hadir / $total) * 100) : 0;
            $status = ($total > 0 && $pct < 75) ? 'Perlu Perhatian' : 'Baik';

            $rows[] = [
                'studentId' => $student->id,
                'studentName' => $student->name,
                'studentEmail' => $student->email,
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpha' => $alpha,
                'total' => $total,
                'pct' => $pct,
                'status' => $status,
            ];
        }

        return $rows;
    }

    /**
     * Mengekspor rekapitulasi kehadiran ke berkas spreadsheet Excel (.xlsx).
     *
     * @param  list<array{studentId: int, studentName: string, studentEmail: string, hadir: int, izin: int, sakit: int, alpha: int, total: int, pct: int, status: string}>  $summaryRows
     * @param  Collection<int, LearningPlan>  $plans
     */
    public function exportExcel(
        SchoolClass $class,
        array $summaryRows,
        Collection $plans,
        ?LearningPlan $selectedPlan,
        User $actor
    ): string {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Kehadiran');

        // Judul Utama Dokumen
        $sheet->setCellValue('A1', 'REKAPITULASI KEHADIRAN SISWA');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F766E');

        // Metadata Dokumen
        $class->loadMissing('homeroomTeacher');
        $homeroomName = $class->homeroomTeacher ? $class->homeroomTeacher->name : '—';
        $scopeText = $selectedPlan ? $selectedPlan->topic : 'Semua Pertemuan ('.$plans->count().' Rencana Ajar)';

        $sheet->setCellValue('A3', 'Kelas / Rombel');
        $sheet->setCellValue('B3', ': '.$class->name.' (Tingkat '.$class->grade.')');
        $sheet->setCellValue('A4', 'Wali Kelas');
        $sheet->setCellValue('B4', ': '.$homeroomName);
        $sheet->setCellValue('A5', 'Cakupan Pertemuan');
        $sheet->setCellValue('B5', ': '.$scopeText);
        $sheet->setCellValue('D3', 'Dicetak Oleh');
        $sheet->setCellValue('E3', ': '.$actor->name);
        $sheet->setCellValue('D4', 'Tanggal Unduh');
        $sheet->setCellValue('E4', ': '.now()->translatedFormat('d F Y H:i'));

        $sheet->getStyle('A3:A5')->getFont()->setBold(true);
        $sheet->getStyle('D3:D4')->getFont()->setBold(true);

        // Header Kolom Tabel
        $headers = [
            'No',
            'ID',
            'Nama Siswa',
            'Email',
            'Hadir (H)',
            'Izin (I)',
            'Sakit (S)',
            'Alpha (A)',
            'Total Pertemuan',
            '% Kehadiran',
            'Keterangan',
        ];

        $headerRow = 7;
        $sheet->fromArray($headers, null, "A{$headerRow}");

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle("A{$headerRow}:K{$headerRow}")->applyFromArray($headerStyle);
        $sheet->getRowDimension($headerRow)->setRowHeight(24);

        // Pengisian Baris Data
        $dataRow = 8;
        $no = 1;
        foreach ($summaryRows as $row) {
            $sheet->setCellValue("A{$dataRow}", $no++);
            $sheet->setCellValue("B{$dataRow}", '#'.$row['studentId']);
            $sheet->setCellValue("C{$dataRow}", $row['studentName']);
            $sheet->setCellValue("D{$dataRow}", $row['studentEmail']);
            $sheet->setCellValue("E{$dataRow}", $row['hadir']);
            $sheet->setCellValue("F{$dataRow}", $row['izin']);
            $sheet->setCellValue("G{$dataRow}", $row['sakit']);
            $sheet->setCellValue("H{$dataRow}", $row['alpha']);
            $sheet->setCellValue("I{$dataRow}", $row['total']);
            $sheet->setCellValue("J{$dataRow}", $row['pct'].'%');
            $sheet->setCellValue("K{$dataRow}", $row['status']);

            // Alignments
            $sheet->getStyle("A{$dataRow}:B{$dataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$dataRow}:J{$dataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Highlight siswa berisiko
            if ($row['status'] === 'Perlu Perhatian') {
                $sheet->getStyle("J{$dataRow}:K{$dataRow}")->getFont()->getColor()->setRGB('DC2626');
                $sheet->getStyle("J{$dataRow}:K{$dataRow}")->getFont()->setBold(true);
            }

            $dataRow++;
        }

        // Garis border tabel
        $lastRow = max(8, $dataRow - 1);
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ];
        $sheet->getStyle("A{$headerRow}:K{$lastRow}")->applyFromArray($borderStyle);

        // Auto-width kolom
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        if ($tempFile === false) {
            throw new \RuntimeException('Gagal membuat file sementara untuk Excel.');
        }

        $writer = new XlsxWriter($spreadsheet);
        $writer->save($tempFile);

        $content = file_get_contents($tempFile) ?: '';
        @unlink($tempFile);

        return $content;
    }

    /**
     * Menyiapkan data terstruktur untuk pratinjau cetak PDF Blade.
     *
     * @param  list<array{studentId: int, studentName: string, studentEmail: string, hadir: int, izin: int, sakit: int, alpha: int, total: int, pct: int, status: string}>  $summaryRows
     * @param  Collection<int, LearningPlan>  $plans
     * @return array{class: array{name: string, grade: int, homeroom: string}, plansCount: int, selectedPlanTopic: ?string, summaryRows: list<array{studentId: int, studentName: string, studentEmail: string, hadir: int, izin: int, sakit: int, alpha: int, total: int, pct: int, status: string}>, stats: array{totalStudents: int, avgPct: int, warningCount: int}, actorName: string, schoolName: string, headmaster: string}
     */
    public function preparePrintData(
        SchoolClass $class,
        array $summaryRows,
        Collection $plans,
        ?LearningPlan $selectedPlan,
        User $actor
    ): array {
        $class->loadMissing('homeroomTeacher');
        $homeroomName = $class->homeroomTeacher ? $class->homeroomTeacher->name : '—';

        $totalStudents = count($summaryRows);
        $avgPct = $totalStudents > 0
            ? (int) round((float) collect($summaryRows)->avg('pct'))
            : 0;

        $warningCount = collect($summaryRows)
            ->where('status', 'Perlu Perhatian')
            ->count();

        /** @var SettingService $settingService */
        $settingService = app(SettingService::class);
        $schoolName = $settingService->getString('school.name', 'SMP Negeri 1 Aksara');
        $headmaster = $settingService->getString('school.headmaster', '');

        return [
            'class' => [
                'name' => $class->name,
                'grade' => (int) $class->grade,
                'homeroom' => $homeroomName,
            ],
            'plansCount' => $plans->count(),
            'selectedPlanTopic' => $selectedPlan?->topic,
            'summaryRows' => $summaryRows,
            'stats' => [
                'totalStudents' => $totalStudents,
                'avgPct' => $avgPct,
                'warningCount' => $warningCount,
            ],
            'actorName' => $actor->name,
            'schoolName' => $schoolName,
            'headmaster' => $headmaster,
        ];
    }
}
