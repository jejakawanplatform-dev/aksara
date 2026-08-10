<?php

namespace App\Http\Controllers\Attendance;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\LearningPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function edit(LearningPlan $plan): Response
    {
        abort_unless($plan->teacher_id === Auth::id(), 403);

        $plan->load('class.students');
        $students = $plan->class?->students ?? collect();
        $existing = AttendanceRecord::where('plan_id', $plan->id)->get()->keyBy('student_id');

        $rows = $students->map(function ($student) use ($existing) {
            $record = $existing->get($student->id);

            return [
                'id' => $student->id,
                'name' => $student->name,
                'status' => $record?->status?->value ?? AttendanceStatus::Present->value,
                'notes' => $record?->notes ?? '',
            ];
        })->values();

        return Inertia::render('Attendance/Form', [
            'plan' => [
                'id' => $plan->id,
                'topic' => $plan->topic,
                'className' => $plan->class?->name,
            ],
            'students' => $rows,
            'statuses' => [
                AttendanceStatus::Present->value => 'Hadir',
                AttendanceStatus::Excused->value => 'Izin',
                AttendanceStatus::Sick->value => 'Sakit',
                AttendanceStatus::Absent->value => 'Alpha',
            ],
            'saveUrl' => route('attendance.save', $plan),
            'plansUrl' => route('plans.index'),
        ]);
    }

    public function save(Request $request, LearningPlan $plan): RedirectResponse
    {
        abort_unless($plan->teacher_id === Auth::id(), 403);

        $studentIds = ($plan->class?->students ?? collect())->pluck('id')->all();

        $validated = $request->validate([
            'attendance' => ['required', 'array'],
            'attendance.*.status' => ['required', Rule::in(array_column(AttendanceStatus::cases(), 'value'))],
            'attendance.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($validated['attendance'] as $studentId => $row) {
            if (! in_array((int) $studentId, $studentIds, true)) {
                continue;
            }

            AttendanceRecord::updateOrCreate(
                ['plan_id' => $plan->id, 'student_id' => (int) $studentId],
                [
                    'status' => AttendanceStatus::from($row['status']),
                    'notes' => $row['notes'] ?? null,
                ]
            );
        }

        return back()->with('message', 'Data kehadiran berhasil disimpan!');
    }
}
