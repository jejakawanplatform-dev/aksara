<?php

namespace App\Http\Controllers;

use App\Models\CurriculumAtpItem;
use App\Models\CurriculumCp;
use App\Models\Subject;
use App\Services\CurriculumExportImportService;
use App\Services\SettingService;
use Illuminate\Http\Request;

class CurriculumExportController extends Controller
{
    public function __construct(protected CurriculumExportImportService $exportService) {}

    public function exportCpTp(Request $request, Subject $subject, string $format)
    {
        $safeCode = preg_replace('/[^A-Za-z0-9_-]/', '', $subject->code ?: 'MAPEL');
        $filename = "CP_TP_{$safeCode}_".now()->format('Ymd_His');

        if ($format === 'excel') {
            $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
            file_put_contents($tempFile, $this->exportService->exportCpTpExcel($subject));

            return response()->download($tempFile, "{$filename}.xlsx", [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        if ($format === 'word') {
            $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
            file_put_contents($tempFile, $this->exportService->exportCpTpWord($subject));

            return response()->download($tempFile, "{$filename}.docx", [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        }

        if ($format === 'pdf') {
            /** @var SettingService $settingService */
            $settingService = app(SettingService::class);
            $schoolName = (string) $settingService->get('school.name', 'SMP Negeri 1 Aksara');

            $cps = CurriculumCp::query()
                ->with(['tps' => fn ($q) => $q->orderBy('sequence')])
                ->where('subject_id', $subject->id)
                ->orderBy('sequence')
                ->get();

            return view('exports.cp-tp-pdf', compact('subject', 'cps', 'schoolName'));
        }

        abort(404);
    }

    public function exportAtp(Request $request, Subject $subject, string $format)
    {
        $grade = $request->query('grade') ? (int) $request->query('grade') : null;
        $safeCode = preg_replace('/[^A-Za-z0-9_-]/', '', $subject->code ?: 'MAPEL');
        $filename = "ATP_{$safeCode}_".($grade ? "K{$grade}_" : '').now()->format('Ymd_His');

        if ($format === 'excel') {
            $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
            file_put_contents($tempFile, $this->exportService->exportAtpExcel($subject, $grade));

            return response()->download($tempFile, "{$filename}.xlsx", [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        if ($format === 'word') {
            $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
            file_put_contents($tempFile, $this->exportService->exportAtpWord($subject, $grade));

            return response()->download($tempFile, "{$filename}.docx", [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        }

        if ($format === 'pdf') {
            /** @var SettingService $settingService */
            $settingService = app(SettingService::class);
            $schoolName = (string) $settingService->get('school.name', 'SMP Negeri 1 Aksara');

            $atpItems = CurriculumAtpItem::query()
                ->with(['tp', 'semester'])
                ->where('subject_id', $subject->id)
                ->when($grade, fn ($q) => $q->where('grade', $grade))
                ->orderBy('grade')
                ->orderBy('sequence')
                ->get();

            return view('exports.atp-pdf', compact('subject', 'atpItems', 'schoolName', 'grade'));
        }

        abort(404);
    }
}
