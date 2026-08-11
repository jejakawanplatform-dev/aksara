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

namespace App\Http\Controllers\References;

use App\Http\Controllers\Controller;
use App\Models\LearningPlan;
use App\Models\Subject;
use App\Services\CurriculumExportImportService;
use App\Support\Access\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReferenceImportController extends Controller
{
    public function importCpTp(Request $request, CurriculumExportImportService $service): RedirectResponse
    {
        $data = $request->validate([
            'subject_id' => 'required|integer|exists:subjects,id',
            'importFile' => 'required|file|max:5120',
        ]);

        abort_unless($this->canManageSubject((int) $data['subject_id']), 403);

        $count = $service->importCpTp($data['importFile']->getRealPath(), (int) $data['subject_id']);

        return redirect()->route('references.index', ['tab' => 'cp', 'subjectId' => $data['subject_id']])
            ->with('message', "Berhasil mengimpor {$count} item TP/CP!");
    }

    public function importAtp(Request $request, CurriculumExportImportService $service): RedirectResponse
    {
        $data = $request->validate([
            'subject_id' => 'required|integer|exists:subjects,id',
            'importFile' => 'required|file|max:5120',
        ]);

        abort_unless($this->canManageSubject((int) $data['subject_id']), 403);

        $count = $service->importAtp($data['importFile']->getRealPath(), (int) $data['subject_id']);

        return redirect()->route('references.index', ['tab' => 'atp', 'subjectId' => $data['subject_id']])
            ->with('message', "Berhasil mengimpor {$count} item ATP!");
    }

    private function canManageSubject(int $subjectId): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->can(PermissionCatalog::REFERENCES_MANAGE) || $user->isAdmin()) {
            return true;
        }

        return DB::table('subject_teachers')
            ->where('subject_id', $subjectId)
            ->where('teacher_id', $user->id)
            ->exists()
            || LearningPlan::where('teacher_id', $user->id)->where('subject_id', $subjectId)->exists()
            || Subject::where('id', $subjectId)->where('code', 'INF')->exists();
    }
}
