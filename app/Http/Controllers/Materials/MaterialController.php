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
use App\Enums\QuizStatus;
use App\Http\Controllers\Controller;
use App\Models\LearningEvent;
use App\Models\LearningMaterial;
use App\Models\LearningPlan;
use App\Models\User;
use App\Support\MaterialContentHtml;
use App\Support\SubjectContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MaterialController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $search = (string) $request->query('search', '');
        $status = (string) $request->query('status', '');
        $perPage = (int) $request->query('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $query = LearningMaterial::query()->with(['plan.subject', 'plan.class']);

        if ($user->isStudent()) {
            $classIds = $user->classIds();
            $query
                ->where('status', MaterialStatus::Published)
                ->whereHas('plan', fn ($q) => $q->whereIn('class_id', $classIds));
        } else {
            $planIds = LearningPlan::query()->forCurrentUser()->pluck('id');
            $query->whereIn('plan_id', $planIds);
            if ($status !== '') {
                $query->where('status', $status);
            }
        }

        $query->when(
            $search !== '',
            fn ($q) => $q->whereHas('plan', fn ($p) => $p->where('topic', 'like', "%{$search}%"))
        );

        $materials = $query
            ->when(
                $user->isStudent(),
                fn ($q) => $q->latest('published_at'),
                fn ($q) => $q->latest()
            )
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (LearningMaterial $m) {
                $plan = $m->plan;
                $content = is_array($m->content) ? $m->content : [];
                $title = (isset($content['title']) && is_scalar($content['title']))
                    ? (string) $content['title']
                    : ($plan ? $plan->topic : 'Materi');

                return [
                    'id' => $m->id,
                    'title' => $title,
                    'status' => $m->status->value,
                    'statusLabel' => $m->status->label(),
                    'subject' => $plan && $plan->subject ? $plan->subject->name : '-',
                    'className' => $plan && $plan->class ? $plan->class->name : ($plan && $plan->grade ? (string) $plan->grade : '-'),
                    'durationMinutes' => $plan ? $plan->duration_minutes : 0,
                    'showUrl' => route('materials.show', $m),
                    'editUrl' => route('materials.edit', $m),
                    'exportPdf' => route('materials.export.single', [$m, 'pdf']),
                    'exportWord' => route('materials.export.single', [$m, 'word']),
                    'exportMarkdown' => route('materials.export.single', [$m, 'markdown']),
                ];
            });

        return Inertia::render('Materials/Index', [
            'materials' => $materials,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
            'indexUrl' => route('materials.index'),
            'bulkDestroyUrl' => $user->isStudent() ? null : route('materials.bulk-destroy'),
            'isStudent' => $user->isStudent(),
        ]);
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        // Hanya Admin dan Guru yang boleh melakukan aksi massal
        abort_if($user->isStudent(), 403);

        $validated = $request->validate([
            'ids' => ['required_without:select_all_matching', 'array'],
            'ids.*' => ['integer', 'exists:learning_materials,id'],
            'select_all_matching' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        // Scope ke materi yang boleh diakses user ini (plan milik sendiri / admin semua)
        $planIds = LearningPlan::query()->forCurrentUser()->pluck('id');
        $query = LearningMaterial::query()->whereIn('plan_id', $planIds);

        if (! empty($validated['select_all_matching'])) {
            $search = (string) ($validated['search'] ?? '');
            $status = (string) ($validated['status'] ?? '');
            $query
                ->when($search !== '', fn ($q) => $q->whereHas('plan', fn ($p) => $p->where('topic', 'like', "%{$search}%")))
                ->when($status !== '', fn ($q) => $q->where('status', $status));
        } else {
            $query->whereIn('id', $validated['ids'] ?? []);
        }

        $materials = $query->with('events')->get();

        if ($materials->isEmpty()) {
            return back()->with('error', 'Tidak ada materi yang dipilih untuk dihapus.');
        }

        [$deletedCount, $skippedPublished] = DB::transaction(function () use ($materials): array {
            $deleted = 0;
            $skipped = 0;

            foreach ($materials as $material) {
                if ($material->events->isNotEmpty()) {
                    $skipped++;

                    continue;
                }

                $material->delete();
                $deleted++;
            }

            return [$deleted, $skipped];
        });

        $messages = [];
        if ($deletedCount > 0) {
            $messages[] = "{$deletedCount} materi berhasil dihapus.";
        }
        if ($skippedPublished > 0) {
            $messages[] = "{$skippedPublished} materi dilewati karena sudah dibaca oleh siswa.";
        }

        return redirect()->route('materials.index')->with(
            $deletedCount > 0 ? 'message' : 'error',
            implode(' ', $messages)
        );
    }

    public function show(LearningMaterial $material): Response
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

        $material->load(['plan.subject', 'plan.quizzes', 'plan.class']);
        $content = is_array($material->content) ? $material->content : [];
        $materialData = (isset($content['material']) && is_array($content['material'])) ? $content['material'] : [];

        $rawSections = (isset($content['sections']) && is_iterable($content['sections']))
            ? $content['sections']
            : ((isset($materialData['sections']) && is_iterable($materialData['sections'])) ? $materialData['sections'] : []);

        $sections = [];
        foreach ($rawSections as $section) {
            $heading = '';
            $body = '';
            if (is_array($section)) {
                $heading = isset($section['heading']) && is_scalar($section['heading']) ? (string) $section['heading'] : '';
                $body = isset($section['body']) && is_string($section['body']) ? $section['body'] : '';
            } elseif (is_scalar($section)) {
                $heading = (string) $section;
            }
            if ($body !== '') {
                $body = MaterialContentHtml::forStudent($body);
            }
            $sections[] = ['heading' => $heading, 'body' => $body];
        }

        $rawReflection = $content['reflectionQuestion'] ?? ($materialData['reflectionQuestion'] ?? null);
        $reflectionList = is_array($rawReflection)
            ? $rawReflection
            : (is_string($rawReflection) && trim($rawReflection) !== '' ? [$rawReflection] : []);

        $publishedQuiz = $plan->quizzes->firstWhere('status', QuizStatus::Published);

        $materialTitle = $plan->topic;
        if (isset($content['title']) && is_string($content['title']) && $content['title'] !== '') {
            $materialTitle = $content['title'];
        } elseif (isset($materialData['title']) && is_string($materialData['title']) && $materialData['title'] !== '') {
            $materialTitle = $materialData['title'];
        }

        $reflections = [];
        foreach ($reflectionList as $item) {
            if (is_array($item)) {
                $scalars = [];
                foreach ($item as $subItem) {
                    if (is_scalar($subItem)) {
                        $scalars[] = (string) $subItem;
                    }
                }
                $reflections[] = implode('; ', $scalars);
            } elseif (is_scalar($item)) {
                $reflections[] = (string) $item;
            }
        }

        return Inertia::render('Materials/Show', [
            'material' => [
                'id' => $material->id,
                'title' => $materialTitle,
                'status' => $material->status->value,
                'sections' => $sections,
                'reflections' => $reflections,
                'plan' => [
                    'topic' => $plan->topic,
                    'grade' => $plan->grade,
                    'subject' => $plan->subject->name ?? '-',
                    'className' => $plan->class->name ?? (string) $plan->grade,
                ],
            ],
            'isStem' => SubjectContext::isStem($plan->subject),
            'isStudent' => $isStudent,
            'urls' => [
                'index' => route('materials.index'),
                'edit' => route('materials.edit', $material),
                'quizAttempt' => $publishedQuiz ? route('quiz.attempt', $publishedQuiz) : null,
                'exportPdf' => route('materials.export.single', [$material, 'pdf']),
                'exportWord' => route('materials.export.single', [$material, 'word']),
                'exportMarkdown' => route('materials.export.single', [$material, 'markdown']),
            ],
        ]);
    }
}
