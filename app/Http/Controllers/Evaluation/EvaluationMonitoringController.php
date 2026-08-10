<?php

namespace App\Http\Controllers\Evaluation;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\TeacherEvaluation;
use App\Models\User;
use App\Support\Access\PermissionCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationMonitoringController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();

        abort_unless(
            $user?->isAdmin() || $user?->can(PermissionCatalog::EVALUATION_MANAGE),
            403
        );

        $isAdmin = (bool) $user?->isAdmin();
        $search = (string) $request->query('search', '');
        $teacherFilter = (string) $request->query('teacher', '');
        $subjectFilter = (string) $request->query('subject', '');

        $teachers = $isAdmin
            ? User::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        $subjects = Subject::query()->orderBy('name')->get(['id', 'name']);

        $evaluations = TeacherEvaluation::query()
            ->when(! $isAdmin, fn ($q) => $q->where('teacher_id', $user->id))
            ->when($teacherFilter !== '', fn ($q) => $q->where('teacher_id', $teacherFilter))
            ->when($subjectFilter !== '', fn ($q) => $q->whereHas('plan', fn ($p) => $p->where('subject_id', $subjectFilter)))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('notes', 'like', "%{$search}%")
                    ->orWhere('challenges', 'like', "%{$search}%")
                    ->orWhere('next_action', 'like', "%{$search}%")
                    ->orWhereHas('plan', fn ($p) => $p->where('topic', 'like', "%{$search}%"));
            }))
            ->with(['teacher', 'plan.subject', 'plan.class'])
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (TeacherEvaluation $eval) => [
                'id' => $eval->id,
                'notes' => $eval->notes,
                'challenges' => $eval->challenges,
                'nextAction' => $eval->next_action,
                'createdAt' => $eval->created_at?->format('d M Y, H:i'),
                'teacherName' => $eval->teacher?->name,
                'topic' => $eval->plan?->topic,
                'subjectName' => $eval->plan?->subject?->name,
                'className' => $eval->plan?->class?->name,
            ]);

        return Inertia::render('Evaluation/Monitoring', [
            'evaluations' => $evaluations,
            'teachers' => $teachers,
            'subjects' => $subjects,
            'isAdmin' => $isAdmin,
            'filters' => [
                'search' => $search,
                'teacher' => $teacherFilter,
                'subject' => $subjectFilter,
            ],
            'indexUrl' => route('evaluations.monitoring'),
        ]);
    }
}
