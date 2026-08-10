<?php

namespace App\Http\Controllers\Materials;

use App\Enums\MaterialStatus;
use App\Http\Controllers\Controller;
use App\Models\LearningEvent;
use App\Models\LearningMaterial;
use App\Models\LearningPlan;
use App\Support\MaterialContentHtml;
use App\Support\SubjectContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MaterialController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = LearningMaterial::query()->with(['plan.subject', 'plan.class']);

        if ($user->isStudent()) {
            $classIds = $user->classIds();
            $materials = $query
                ->where('status', MaterialStatus::Published)
                ->whereHas('plan', fn ($q) => $q->whereIn('class_id', $classIds))
                ->latest('published_at')
                ->get();
        } else {
            $planIds = LearningPlan::query()->forCurrentUser()->pluck('id');
            $materials = $query
                ->whereIn('plan_id', $planIds)
                ->latest()
                ->get();
        }

        return Inertia::render('Materials/Index', [
            'materials' => $materials->map(fn (LearningMaterial $m) => [
                'id' => $m->id,
                'title' => $m->content['title'] ?? $m->plan->topic,
                'status' => $m->status->value ?? 'draft',
                'statusLabel' => $m->status->label(),
                'subject' => $m->plan->subject->name ?? '-',
                'className' => $m->plan->class->name ?? $m->plan->grade,
                'durationMinutes' => $m->plan->duration_minutes,
                'showUrl' => route('materials.show', $m),
            ]),
            'isStudent' => $user->isStudent(),
        ]);
    }

    public function show(LearningMaterial $material): Response
    {
        $user = Auth::user();
        $isTeacherOwner = $user->isTeacher() && $material->plan?->teacher_id === $user->id;
        $isAdmin = $user->isAdmin();
        $isStudent = $user->isStudent();

        if ($isStudent) {
            abort_unless($material->status === MaterialStatus::Published, 403);
            abort_unless($user->belongsToClass($material->plan->class_id), 403);

            LearningEvent::create([
                'material_id' => $material->id,
                'student_id' => $user->id,
                'event_type' => 'material_opened',
                'occurred_at' => now(),
            ]);
        } else {
            abort_unless($isTeacherOwner || $isAdmin, 403);
        }

        $material->load(['plan.subject', 'plan.quizzes', 'plan.class']);
        $content = is_array($material->content) ? $material->content : [];
        $rawSections = $content['sections'] ?? ($content['material']['sections'] ?? []);
        $sections = [];
        foreach ($rawSections as $section) {
            $heading = is_array($section) ? ($section['heading'] ?? '') : (string) $section;
            $body = is_array($section) ? (string) ($section['body'] ?? '') : '';
            if ($isStudent && $body !== '') {
                $body = MaterialContentHtml::forStudent($body);
            }
            $sections[] = ['heading' => $heading, 'body' => $body];
        }

        $rawReflection = $content['reflectionQuestion'] ?? ($content['material']['reflectionQuestion'] ?? null);
        $reflectionList = is_array($rawReflection)
            ? $rawReflection
            : (is_string($rawReflection) && trim($rawReflection) !== '' ? [$rawReflection] : []);

        $publishedQuiz = $material->plan->quizzes->firstWhere('status', 'published')
            ?? $material->plan->quizzes->firstWhere('status.value', 'published');

        // Enum cast may make status an enum
        if (! $publishedQuiz) {
            $publishedQuiz = $material->plan->quizzes->first(function ($q) {
                $status = $q->status;
                $value = $status instanceof \BackedEnum ? $status->value : (string) $status;

                return $value === 'published';
            });
        }

        return Inertia::render('Materials/Show', [
            'material' => [
                'id' => $material->id,
                'title' => $content['title'] ?? ($content['material']['title'] ?? $material->plan->topic),
                'status' => $material->status->value ?? 'draft',
                'sections' => $sections,
                'reflections' => array_map(
                    fn ($item) => is_array($item) ? implode('; ', $item) : (string) $item,
                    $reflectionList
                ),
                'plan' => [
                    'topic' => $material->plan->topic,
                    'grade' => $material->plan->grade,
                    'subject' => $material->plan->subject->name ?? '-',
                    'className' => $material->plan->class->name ?? $material->plan->grade,
                ],
            ],
            'isStem' => SubjectContext::isStem($material->plan->subject),
            'isStudent' => $isStudent,
            'urls' => [
                'index' => route('materials.index'),
                'edit' => route('materials.edit', $material),
                'quizAttempt' => $publishedQuiz ? route('quiz.attempt', $publishedQuiz) : null,
            ],
        ]);
    }
}
