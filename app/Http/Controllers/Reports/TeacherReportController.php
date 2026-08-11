<?php

namespace App\Http\Controllers\Reports;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\LearningPlan;
use App\Models\TeacherEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TeacherReportController extends Controller
{
    public function index(Request $request): Response
    {
        $teacher = Auth::user();
        $perPage = (int) $request->query('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $reportData = LearningPlan::where('teacher_id', $teacher->id)
            ->with(['class', 'subject', 'attendance', 'quizzes.attempts'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (LearningPlan $plan) use ($teacher) {
                $attendances = $plan->attendance;
                $totalSiswa = $attendances->count();
                $hadirCount = $attendances->where('status', AttendanceStatus::Present)->count();

                $attempts = $plan->quizzes->flatMap->attempts;
                $avgScore = $attempts->count() > 0 ? (int) round($attempts->avg('score')) : null;

                $evaluation = TeacherEvaluation::where('plan_id', $plan->id)
                    ->where('teacher_id', $teacher->id)
                    ->first();

                return [
                    'planId' => $plan->id,
                    'topic' => $plan->topic,
                    'className' => $plan->class?->name,
                    'subjectName' => $plan->subject?->name,
                    'totalSiswa' => $totalSiswa,
                    'hadirCount' => $hadirCount,
                    'quizCount' => $attempts->count(),
                    'avgScore' => $avgScore,
                    'hasEval' => $evaluation !== null,
                    'attendanceUrl' => route('attendance.form', $plan),
                    'evaluationUrl' => route('evaluation.form', $plan),
                ];
            });

        return Inertia::render('Reports/Teacher', [
            'reportData' => $reportData,
            'filters' => [
                'per_page' => $perPage,
            ],
            'indexUrl' => route('reports.guru'),
            'plansCreateUrl' => route('plans.create'),
        ]);
    }
}
