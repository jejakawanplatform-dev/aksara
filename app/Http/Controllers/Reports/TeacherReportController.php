<?php

namespace App\Http\Controllers\Reports;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\LearningPlan;
use App\Models\TeacherEvaluation;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TeacherReportController extends Controller
{
    public function index(): Response
    {
        $teacher = Auth::user();

        $plans = LearningPlan::where('teacher_id', $teacher->id)
            ->with(['class', 'subject', 'attendance', 'quizzes.attempts'])
            ->latest()
            ->get();

        $reportData = $plans->map(function (LearningPlan $plan) use ($teacher) {
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
        })->values();

        return Inertia::render('Reports/Teacher', [
            'reportData' => $reportData,
            'plansCreateUrl' => route('plans.create'),
        ]);
    }
}
