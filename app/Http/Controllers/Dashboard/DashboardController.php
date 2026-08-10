<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\MaterialStatus;
use App\Enums\PlanStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AiGeneration;
use App\Models\LearningMaterial;
use App\Models\LearningPlan;
use App\Models\SchoolClass;
use App\Models\TeacherEvaluation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response|SymfonyResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return $this->admin();
        }

        if ($user->isTeacher()) {
            return $this->guru($user);
        }

        if ($user->isHomeroomTeacher()) {
            return $this->waliKelas($user);
        }

        if ($user->isStudent()) {
            return $this->siswa($user);
        }

        if ($user->isParent()) {
            return app(WaliMuridController::class)->index();
        }

        return Inertia::render('Dashboard/Generic', [
            'userName' => $user->name,
        ]);
    }

    private function admin(): Response
    {
        $counts = [
            'admin' => User::where('role', UserRole::Admin)->count(),
            'teacher' => User::where('role', UserRole::Teacher)->count(),
            'homeroom' => User::where('role', UserRole::HomeroomTeacher)->count(),
            'student' => User::where('role', UserRole::Student)->count(),
            'parent' => User::where('role', UserRole::Parent)->count(),
        ];

        $year = AcademicYear::active();

        return Inertia::render('Dashboard/Admin', [
            'activeYear' => $year?->name,
            'rombelCount' => SchoolClass::count(),
            'counts' => $counts,
            'urls' => [
                'users' => route('users.index'),
                'access' => route('access.index'),
                'references' => route('references.index'),
            ],
        ]);
    }

    private function guru(User $user): Response
    {
        $totalPlans = LearningPlan::where('teacher_id', $user->id)->count();
        $publishedPlans = LearningPlan::where('teacher_id', $user->id)
            ->where('status', PlanStatus::Published)
            ->count();
        $draftPlans = LearningPlan::where('teacher_id', $user->id)
            ->where('status', PlanStatus::Draft)
            ->count();

        $totalMaterials = LearningMaterial::whereHas('plan', fn ($q) => $q->where('teacher_id', $user->id))->count();
        $publishedMaterials = LearningMaterial::whereHas('plan', fn ($q) => $q->where('teacher_id', $user->id))
            ->where('status', MaterialStatus::Published)
            ->count();

        $evaluationsCount = TeacherEvaluation::where('teacher_id', $user->id)->count();

        $todayAiCount = AiGeneration::where('created_by', $user->id)
            ->whereDate('created_at', now()->today())
            ->count();
        $dailyLimit = (int) setting('ai.daily_limit_per_teacher', 20);
        $aiPercentage = min(100, (int) round(($todayAiCount / max(1, $dailyLimit)) * 100));

        $recentPlans = LearningPlan::with(['subject', 'class', 'semester'])
            ->where('teacher_id', $user->id)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (LearningPlan $plan) => [
                'id' => $plan->id,
                'topic' => $plan->topic,
                'duration_minutes' => $plan->duration_minutes,
                'phase' => $plan->phase,
                'status' => $plan->status instanceof \BackedEnum ? $plan->status->value : (string) $plan->status,
                'subject' => $plan->subject?->name,
                'classLabel' => $plan->class?->label(),
                'editUrl' => route('plans.edit', $plan),
                'draftUrl' => route('plans.draft', $plan),
            ]);

        $recentMaterials = LearningMaterial::with(['plan.subject', 'plan.class'])
            ->whereHas('plan', fn ($q) => $q->where('teacher_id', $user->id))
            ->orderByDesc('created_at')
            ->limit(3)
            ->get()
            ->map(function (LearningMaterial $mat) {
                $sections = $mat->content['sections'] ?? [];

                return [
                    'id' => $mat->id,
                    'title' => $mat->content['title'] ?? $mat->plan?->topic,
                    'sectionsCount' => is_countable($sections) ? count($sections) : 0,
                    'subject' => $mat->plan?->subject?->name,
                    'classLabel' => $mat->plan?->class?->label(),
                    'url' => route('materials.show', $mat),
                ];
            });

        $classesTaught = SchoolClass::withCount('students')
            ->where(function ($q) use ($user) {
                $q->where('homeroom_teacher_id', $user->id)
                    ->orWhereHas('learningPlans', fn ($q) => $q->where('teacher_id', $user->id));
            })
            ->distinct()
            ->get()
            ->map(fn (SchoolClass $cls) => [
                'id' => $cls->id,
                'grade' => $cls->grade,
                'label' => $cls->label(),
                'studentsCount' => $cls->students_count,
            ]);

        return Inertia::render('Dashboard/Guru', [
            'userName' => $user->name,
            'roleLabel' => $user->role->label(),
            'metrics' => [
                'totalPlans' => $totalPlans,
                'publishedPlans' => $publishedPlans,
                'draftPlans' => $draftPlans,
                'totalMaterials' => $totalMaterials,
                'publishedMaterials' => $publishedMaterials,
                'evaluationsCount' => $evaluationsCount,
                'todayAiCount' => $todayAiCount,
                'dailyLimit' => $dailyLimit,
                'aiPercentage' => $aiPercentage,
            ],
            'recentPlans' => $recentPlans,
            'recentMaterials' => $recentMaterials,
            'classesTaught' => $classesTaught,
            'urls' => [
                'plansIndex' => route('plans.index'),
                'plansCreateAi' => route('plans.create', ['mode' => 'ai']),
                'plansCreateManual' => route('plans.create', ['mode' => 'manual']),
                'materialsIndex' => route('materials.index'),
                'reportsGuru' => route('reports.guru'),
                'evaluationsMonitoring' => route('evaluations.monitoring'),
            ],
        ]);
    }

    private function waliKelas(User $user): Response
    {
        $classes = SchoolClass::where('homeroom_teacher_id', $user->id)
            ->withCount('students')
            ->get()
            ->map(fn (SchoolClass $class) => [
                'id' => $class->id,
                'name' => $class->name,
                'grade' => $class->grade,
                'studentsCount' => $class->students_count,
            ]);

        return Inertia::render('Dashboard/WaliKelas', [
            'userName' => $user->name,
            'classes' => $classes,
            'attendanceSummaryUrl' => route('attendance.summary'),
        ]);
    }

    private function siswa(User $user): Response
    {
        $classIds = $user->classIds();

        $materials = LearningMaterial::query()
            ->where('status', MaterialStatus::Published)
            ->whereHas('plan', fn ($q) => $q->whereIn('class_id', $classIds))
            ->with('plan.subject')
            ->latest('published_at')
            ->take(6)
            ->get()
            ->map(fn (LearningMaterial $material) => [
                'id' => $material->id,
                'title' => $material->content['title'] ?? $material->plan?->topic,
                'subject' => $material->plan?->subject?->name,
                'grade' => $material->plan?->grade,
                'url' => route('materials.show', $material),
            ]);

        return Inertia::render('Dashboard/Siswa', [
            'userName' => $user->name,
            'materials' => $materials,
            'materialsIndexUrl' => route('materials.index'),
        ]);
    }
}
