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

namespace App\Http\Controllers\Materials;

use App\Enums\MaterialStatus;
use App\Http\Controllers\Controller;
use App\Models\LearningEvent;
use App\Models\LearningMaterial;
use App\Models\LearningPlan;
use App\Models\User;
use App\Services\MaterialExportService;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class MaterialExportController extends Controller
{
    public function __construct(
        protected MaterialExportService $exportService
    ) {}

    /**
     * Ekspor satu materi pembelajaran ke format yang diminta (PDF, Word, Markdown).
     */
    public function exportSingle(Request $request, LearningMaterial $material, string $format): SymfonyResponse|View
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $plan = $material->plan;
        abort_unless($plan instanceof LearningPlan, 404);

        $isStudent = $user->isStudent();
        $isTeacherOwner = $plan->teacher_id === $user->id;
        $isAdmin = $user->isAdmin();

        if ($isStudent) {
            abort_unless($material->status === MaterialStatus::Published, 403);
            abort_unless($user->belongsToClass($plan->class_id), 403);

            LearningEvent::firstOrCreate([
                'material_id' => $material->id,
                'student_id' => $user->id,
                'event_type' => 'material_opened',
                'occurred_at' => now(),
            ]);
        } else {
            abort_unless($isTeacherOwner || $isAdmin, 403);
        }

        $safeTopic = (string) preg_replace('/[^A-Za-z0-9_-]/', '', str_replace(' ', '_', $plan->topic));
        if ($safeTopic === '') {
            $safeTopic = 'Materi';
        }
        $filename = "Bahan_Ajar_{$safeTopic}_".now()->format('Ymd');

        if ($format === 'pdf') {
            $printData = $this->exportService->preparePrintData($material);

            /** @var SettingService $settingService */
            $settingService = app(SettingService::class);
            $schoolName = $settingService->getString('school.name', 'SMP Negeri 1 Aksara');

            return view('exports.material-pdf', array_merge($printData, [
                'schoolName' => $schoolName,
            ]));
        }

        if ($format === 'word') {
            $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
            if ($tempFile === false) {
                abort(500);
            }
            file_put_contents($tempFile, $this->exportService->exportWord($material));

            return response()->download($tempFile, "{$filename}.docx", [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        }

        if ($format === 'markdown') {
            $tempFile = tempnam(sys_get_temp_dir(), 'md_');
            if ($tempFile === false) {
                abort(500);
            }
            file_put_contents($tempFile, $this->exportService->exportMarkdown($material));

            return response()->download($tempFile, "{$filename}.md", [
                'Content-Type' => 'text/markdown; charset=UTF-8',
            ])->deleteFileAfterSend(true);
        }

        abort(404);
    }
}
