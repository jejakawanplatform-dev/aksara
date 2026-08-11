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

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\LearningPlan;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceSummaryController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $classId = $request->query('classId') ? (int) $request->query('classId') : null;
        $planId = $request->query('planId') ? (int) $request->query('planId') : null;
        $perPage = (int) $request->query('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $allowedClassIds = $this->allowedClassIds($user);
        $classes = SchoolClass::query()
            ->whereIn('id', $allowedClassIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($classId !== null && ! $allowedClassIds->contains($classId)) {
            abort(403);
        }

        $plans = collect();
        $allowedPlanIds = collect();
        $summaryData = $this->emptyPaginator($perPage);

        if ($classId !== null) {
            // Wali kelas / admin: semua rencana di kelas. Guru mapel: hanya miliknya.
            $plans = LearningPlan::query()
                ->where('class_id', $classId)
                ->when(
                    $user->isTeacher(),
                    fn ($q) => $q->where('teacher_id', $user->id)
                )
                ->orderBy('topic')
                ->get(['id', 'topic']);
            $allowedPlanIds = $plans->pluck('id');

            if ($planId !== null && ! $allowedPlanIds->contains($planId)) {
                abort(403);
            }

            $planIdsForSummary = $planId !== null
                ? collect([$planId])
                : $allowedPlanIds;

            $class = SchoolClass::query()->find($classId);

            if ($class !== null) {
                $summaryData = $class->students()
                    ->orderBy('name')
                    ->paginate($perPage)
                    ->withQueryString()
                    ->through(function ($student) use ($planIdsForSummary) {
                        $records = $student->attendances()
                            ->whereIn('plan_id', $planIdsForSummary)
                            ->get();

                        $hadir = $records->where('status', AttendanceStatus::Present)->count();
                        $total = $records->count();

                        return [
                            'studentId' => $student->id,
                            'studentName' => $student->name,
                            'hadir' => $hadir,
                            'izin' => $records->where('status', AttendanceStatus::Excused)->count(),
                            'sakit' => $records->where('status', AttendanceStatus::Sick)->count(),
                            'alpha' => $records->where('status', AttendanceStatus::Absent)->count(),
                            'total' => $total,
                            'pct' => $total > 0 ? (int) round(($hadir / $total) * 100) : 0,
                        ];
                    });
            }
        }

        return Inertia::render('Attendance/Summary', [
            'classes' => $classes,
            'plans' => $plans->map(fn (LearningPlan $plan) => [
                'id' => $plan->id,
                'topic' => $plan->topic,
            ]),
            'summaryData' => $summaryData,
            'filters' => [
                'classId' => $classId,
                'planId' => $planId,
                'per_page' => $perPage,
            ],
            'indexUrl' => route('attendance.summary'),
        ]);
    }

    /**
     * @return Collection<int, int>
     */
    private function allowedClassIds(User $user)
    {
        if ($user->isHomeroomTeacher()) {
            return SchoolClass::query()
                ->where('homeroom_teacher_id', $user->id)
                ->pluck('id');
        }

        if ($user->isTeacher()) {
            return LearningPlan::query()
                ->where('teacher_id', $user->id)
                ->pluck('class_id')
                ->unique()
                ->values();
        }

        // Admin atau role lain yang punya attendance.summary via matrix override.
        return SchoolClass::query()->pluck('id');
    }

    private function emptyPaginator(int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, $perPage);
    }
}
