<?php

namespace App\Http\Controllers;

use App\Models\LearningPlan;
use App\Services\LearningPlanExportImportService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LearningPlanExportController extends Controller
{
    public function __construct(protected LearningPlanExportImportService $exportService) {}

    /**
     * Batch export filtered Learning Plans (Excel, Word, PDF)
     */
    public function export(Request $request, string $format)
    {
        $user = Auth::user();
        $query = LearningPlan::query()
            ->forCurrentUser()
            ->with(['teacher', 'class', 'subject', 'academicYear', 'semester']);

        if ($request->query('search')) {
            $query->where('topic', 'like', "%{$request->query('search')}%");
        }

        if ($request->query('subject_id')) {
            $query->where('subject_id', $request->query('subject_id'));
        }

        if ($request->query('teacher_id') && $user->isAdmin()) {
            $query->where('teacher_id', $request->query('teacher_id'));
        }

        if ($request->query('status')) {
            $query->where('status', $request->query('status'));
        }

        $plans = $query->latest()->get();
        $filename = "Modul_Ajar_Rekap_" . now()->format('Ymd_His');

        if ($format === 'excel') {
            $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
            file_put_contents($tempFile, $this->exportService->exportPlansExcel($plans));

            return response()->download($tempFile, "{$filename}.xlsx", [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        if ($format === 'word') {
            $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
            file_put_contents($tempFile, $this->exportService->exportPlansWord($plans));

            return response()->download($tempFile, "{$filename}.docx", [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        }

        if ($format === 'pdf') {
            /** @var SettingService $settingService */
            $settingService = app(SettingService::class);
            $schoolName = (string) $settingService->get('school.name', 'SMP Negeri 1 Aksara');

            return view('exports.plans-pdf', compact('plans', 'schoolName'));
        }

        abort(404);
    }

    /**
     * Export single Learning Plan (Word, PDF)
     */
    public function exportSingle(LearningPlan $plan, string $format)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $plan->teacher_id !== $user->id) {
            abort(403);
        }

        $plan->load(['teacher', 'class', 'subject', 'academicYear', 'semester', 'material']);
        $safeTopic = preg_replace('/[^A-Za-z0-9_-]/', '', str_replace(' ', '_', $plan->topic));
        $filename = "Modul_Ajar_{$safeTopic}_" . now()->format('Ymd');

        if ($format === 'word') {
            $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
            file_put_contents($tempFile, $this->exportService->exportSinglePlanWord($plan));

            return response()->download($tempFile, "{$filename}.docx", [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        }

        if ($format === 'pdf') {
            /** @var SettingService $settingService */
            $settingService = app(SettingService::class);
            $schoolName = (string) $settingService->get('school.name', 'SMP Negeri 1 Aksara');

            return view('exports.single-plan-pdf', compact('plan', 'schoolName'));
        }

        abort(404);
    }

    /**
     * Download Excel template for import
     */
    public function downloadTemplate()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        file_put_contents($tempFile, $this->exportService->downloadTemplate());

        return response()->download($tempFile, "Template_Import_Modul_Ajar.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
