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

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\LearningPlan;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\AttendanceExportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AttendanceExportController extends Controller
{
    public function __construct(
        protected AttendanceExportService $exportService
    ) {}

    /**
     * Mengekspor rekapitulasi kehadiran rombel ke format PDF atau Excel.
     */
    public function export(Request $request, string $format): SymfonyResponse|View
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $classId = (int) $request->query('classId');
        abort_if($classId <= 0, 404);

        $allowedClassIds = $this->allowedClassIds($user);
        abort_unless($allowedClassIds->contains($classId), 403);

        $class = SchoolClass::query()->find($classId);
        abort_unless($class instanceof SchoolClass, 404);

        $planId = $request->query('planId') ? (int) $request->query('planId') : null;

        // Wali kelas / admin: seluruh rencana di kelas. Guru mapel: hanya miliknya.
        $plansQuery = LearningPlan::query()
            ->where('class_id', $classId)
            ->when(
                $user->isTeacher(),
                fn ($q) => $q->where('teacher_id', $user->id)
            )
            ->orderBy('topic');

        $plans = $plansQuery->get();
        $allowedPlanIds = $plans->pluck('id')->map(fn (mixed $id): int => is_numeric($id) ? (int) $id : 0);

        if ($planId !== null && ! $allowedPlanIds->contains($planId)) {
            abort(403);
        }

        $selectedPlan = null;
        if ($planId !== null) {
            $selectedPlan = $plans->firstWhere('id', $planId);
            $planIdsForSummary = collect([$planId]);
        } else {
            $planIdsForSummary = $allowedPlanIds;
        }

        $summaryRows = $this->exportService->buildSummaryRows($class, $planIdsForSummary);

        $safeClassName = (string) preg_replace('/[^A-Za-z0-9_-]/', '', str_replace(' ', '_', $class->name));
        if ($safeClassName === '') {
            $safeClassName = 'Kelas';
        }
        $filename = "Rekap_Kehadiran_{$safeClassName}_".now()->format('Ymd_His');

        if ($format === 'pdf') {
            $printData = $this->exportService->preparePrintData(
                $class,
                $summaryRows,
                $plans,
                $selectedPlan,
                $user
            );

            return view('exports.attendance-pdf', $printData);
        }

        if ($format === 'excel') {
            $content = $this->exportService->exportExcel(
                $class,
                $summaryRows,
                $plans,
                $selectedPlan,
                $user
            );

            $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
            if ($tempFile === false) {
                abort(500);
            }
            file_put_contents($tempFile, $content);

            return response()->download($tempFile, "{$filename}.xlsx", [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        abort(404);
    }

    /**
     * Mendapatkan daftar ID kelas yang diizinkan untuk diakses pengguna.
     *
     * @return Collection<int, int>
     */
    private function allowedClassIds(User $user): Collection
    {
        if ($user->isHomeroomTeacher()) {
            return SchoolClass::query()
                ->where('homeroom_teacher_id', $user->id)
                ->pluck('id')
                ->map(fn (mixed $id): int => is_numeric($id) ? (int) $id : 0);
        }

        if ($user->isTeacher()) {
            return LearningPlan::query()
                ->where('teacher_id', $user->id)
                ->pluck('class_id')
                ->unique()
                ->values()
                ->map(fn (mixed $id): int => is_numeric($id) ? (int) $id : 0);
        }

        return SchoolClass::query()
            ->pluck('id')
            ->map(fn (mixed $id): int => is_numeric($id) ? (int) $id : 0);
    }
}
