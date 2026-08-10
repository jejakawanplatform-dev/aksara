<?php

namespace App\Http\Controllers\Attendance;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\LearningPlan;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceSummaryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $classId = $request->query('classId') ? (int) $request->query('classId') : null;
        $planId = $request->query('planId') ? (int) $request->query('planId') : null;

        $classes = SchoolClass::query()
            ->when(
                $user->isTeacher() && ! $user->isHomeroomTeacher(),
                fn ($q) => $q->whereIn(
                    'id',
                    LearningPlan::where('teacher_id', $user->id)->pluck('class_id')
                )
            )
            ->when(
                $user->isHomeroomTeacher(),
                fn ($q) => $q->where('homeroom_teacher_id', $user->id)
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        $summaryData = [];

        if ($classId && $classes->contains('id', $classId)) {
            $class = SchoolClass::find($classId);
            $students = $class?->students ?? collect();

            foreach ($students as $student) {
                $records = $student->attendances()
                    ->when($planId, fn ($q) => $q->where('plan_id', $planId))
                    ->when(
                        $user->isTeacher() && ! $user->isHomeroomTeacher(),
                        fn ($q) => $q->whereIn(
                            'plan_id',
                            LearningPlan::where('teacher_id', $user->id)->pluck('id')
                        )
                    )
                    ->get();

                $hadir = $records->where('status', AttendanceStatus::Present)->count();
                $total = $records->count();

                $summaryData[] = [
                    'studentId' => $student->id,
                    'studentName' => $student->name,
                    'hadir' => $hadir,
                    'izin' => $records->where('status', AttendanceStatus::Excused)->count(),
                    'sakit' => $records->where('status', AttendanceStatus::Sick)->count(),
                    'alpha' => $records->where('status', AttendanceStatus::Absent)->count(),
                    'total' => $total,
                    'pct' => $total > 0 ? (int) round(($hadir / $total) * 100) : 0,
                ];
            }
        }

        return Inertia::render('Attendance/Summary', [
            'classes' => $classes,
            'summaryData' => $summaryData,
            'filters' => [
                'classId' => $classId,
                'planId' => $planId,
            ],
            'indexUrl' => route('attendance.summary'),
        ]);
    }
}
