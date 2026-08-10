<?php

namespace App\Http\Controllers\Plans;

use App\Enums\MaterialStatus;
use App\Enums\PlanStatus;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AiGeneration;
use App\Models\CurriculumCp;
use App\Models\CurriculumTp;
use App\Models\LearningMaterial;
use App\Models\LearningPlan;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use App\Services\AiDraftService;
use App\Services\LearningPlanExportImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isAdmin = (bool) $user?->isAdmin();

        $search = (string) $request->query('search', '');
        $status = (string) $request->query('status', '');
        $teacher = (string) $request->query('teacher', '');
        $subject = (string) $request->query('subject', '');

        $teachers = $isAdmin
            ? User::query()->orderBy('name')->get(['id', 'name'])
            : collect([]);

        $subjects = $isAdmin
            ? Subject::query()->orderBy('name')->get(['id', 'name'])
            : $user->taughtSubjects()->orderBy('name')->get(['subjects.id', 'subjects.name']);

        $plans = LearningPlan::query()
            ->forCurrentUser()
            ->when($teacher !== '', fn ($q) => $q->where('teacher_id', $teacher))
            ->when($subject !== '', fn ($q) => $q->where('subject_id', $subject))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where('topic', 'like', "%{$search}%"))
            ->with(['teacher', 'class', 'subject', 'aiGenerations', 'material', 'quizzes', 'attendance', 'evaluation'])
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(function (LearningPlan $plan) {
                $hasMaterial = (bool) $plan->material;
                $materialPublished = $hasMaterial
                    && ($plan->material->status?->value ?? $plan->material->status) === 'published';

                return [
                    'id' => $plan->id,
                    'topic' => $plan->topic,
                    'durationMinutes' => $plan->duration_minutes,
                    'phase' => $plan->phase,
                    'status' => $plan->status->value ?? 'draft',
                    'statusLabel' => $plan->status->label(),
                    'teacherName' => $plan->teacher?->name,
                    'subjectName' => $plan->subject?->name,
                    'className' => $plan->class?->name,
                    'hasMaterial' => $hasMaterial,
                    'materialPublished' => $materialPublished,
                    'hasQuiz' => $plan->quizzes->isNotEmpty(),
                    'hasAttendance' => $plan->attendance->isNotEmpty(),
                    'hasEvaluation' => (bool) $plan->evaluation,
                    'isPublished' => $plan->status === PlanStatus::Published,
                    'urls' => [
                        'edit' => route('plans.edit', $plan),
                        'draft' => route('plans.draft', $plan),
                        'quiz' => route('plans.quiz', $plan),
                        'openMaterial' => route('plans.open-material', $plan),
                        'exportWord' => route('plans.export.single', [$plan, 'word']),
                        'exportPdf' => route('plans.export.single', [$plan, 'pdf']),
                        'attendance' => route('attendance.form', $plan),
                        'evaluation' => route('evaluation.form', $plan),
                        'destroy' => route('plans.destroy', $plan),
                    ],
                ];
            });

        $filterQuery = array_filter([
            'search' => $search ?: null,
            'subject_id' => $subject ?: null,
            'teacher_id' => $teacher ?: null,
            'status' => $status ?: null,
        ]);

        return Inertia::render('Plans/Index', [
            'plans' => $plans,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'teacher' => $teacher,
                'subject' => $subject,
            ],
            'teachers' => $teachers,
            'subjects' => $subjects,
            'isAdmin' => $isAdmin,
            'importTemplateUrl' => route('plans.import.template'),
            'importUrl' => route('plans.import'),
            'indexUrl' => route('plans.index'),
            'createAiUrl' => route('plans.create', ['mode' => 'ai']),
            'createManualUrl' => route('plans.create', ['mode' => 'manual']),
            'exportUrls' => [
                'excel' => route('plans.export', array_merge(['format' => 'excel'], $filterQuery)),
                'word' => route('plans.export', array_merge(['format' => 'word'], $filterQuery)),
                'pdf' => route('plans.export', array_merge(['format' => 'pdf'], $filterQuery)),
            ],
            'importErrors' => $request->session()->get('importErrors', []),
        ]);
    }

    public function create(Request $request): Response
    {
        $defaults = $this->resolveCreateDefaults();
        $todayCount = AiGeneration::where('created_by', Auth::id())
            ->whereDate('created_at', now()->today())
            ->count();
        $dailyLimit = (int) setting('ai.daily_limit_per_teacher', 20);

        return Inertia::render('Plans/Create', [
            'mode' => in_array($request->query('mode'), ['ai', 'manual'], true)
                ? $request->query('mode')
                : 'ai',
            'defaults' => $defaults,
            'options' => $this->formOptions($defaults['academic_year_id'] ?? 0, $defaults['subject_id'] ?? 0),
            'dailyAiQuota' => [
                'used' => $todayCount,
                'limit' => $dailyLimit,
            ],
            'storeUrl' => route('plans.store'),
            'indexUrl' => route('plans.index'),
        ]);
    }

    public function store(Request $request, AiDraftService $aiDraftService): RedirectResponse
    {
        $validated = $this->validatePlanFields($request);
        $mode = $request->input('mode', 'manual') === 'ai' ? 'ai' : 'manual';

        if ($mode === 'manual') {
            $this->createPlan($validated);
            return redirect()->route('plans.index')->with('message', 'Rencana pembelajaran draf berhasil dibuat.');
        }

        $todayCount = AiGeneration::where('created_by', Auth::id())
            ->whereDate('created_at', now()->today())
            ->count();
        $dailyLimit = (int) setting('ai.daily_limit_per_teacher', 20);

        if ($todayCount >= $dailyLimit) {
            return back()
                ->withInput()
                ->with('message', "Batas kuota harian generasi AI Anda ({$todayCount}/{$dailyLimit}) telah tercapai. Kuota akan di-reset otomatis besok pukul 00:00 WIB. Silakan gunakan Mode Buat Manual.");
        }

        try {
            $plan = $this->createPlan($validated);
            $subject = Subject::find($validated['subject_id'])?->name ?? 'Umum';

            $output = $aiDraftService->generateDraft([
                'phase' => $validated['phase'],
                'grade' => $validated['grade'],
                'subject' => $subject,
                'topic' => $validated['topic'],
                'duration_minutes' => $validated['duration_minutes'],
                'learning_objectives' => $validated['learning_objectives'],
                'student_needs' => $validated['student_needs'] ?: 'Tidak ada catatan khusus',
                'curriculum_reference' => $validated['curriculum_reference'],
            ]);

            $plan->aiGenerations()->create([
                'created_by' => Auth::id(),
                'input_summary' => [
                    'topic' => $validated['topic'],
                    'subject' => $subject,
                    'grade' => $validated['grade'],
                    'academic_year_id' => $validated['academic_year_id'],
                    'semester_id' => $validated['semester_id'],
                    'curriculum_cp_id' => $validated['curriculum_cp_id'] ?? null,
                    'curriculum_tp_id' => $validated['curriculum_tp_id'] ?? null,
                ],
                'output' => $output,
                'model' => setting('ai.default_model', 'gpt-4o-mini'),
                'review_status' => 'pending',
            ]);

            return redirect()->route('plans.draft', $plan);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('message', $e->getMessage());
        }
    }

    public function edit(LearningPlan $plan): Response
    {
        $this->authorizeOwnerOrAdmin($plan);

        $form = [
            'academic_year_id' => $plan->academic_year_id,
            'semester_id' => $plan->semester_id,
            'class_id' => $plan->class_id,
            'subject_id' => $plan->subject_id,
            'curriculum_cp_id' => $plan->curriculum_cp_id,
            'curriculum_tp_id' => $plan->curriculum_tp_id,
            'phase' => $plan->phase,
            'grade' => $plan->grade,
            'topic' => $plan->topic,
            'duration_minutes' => $plan->duration_minutes,
            'learning_objectives' => $plan->learning_objectives,
            'student_needs' => $plan->student_needs ?? '',
            'curriculum_reference' => $plan->curriculum_reference,
            'status' => $plan->status->value ?? 'draft',
        ];

        return Inertia::render('Plans/Edit', [
            'plan' => [
                'id' => $plan->id,
                'topic' => $plan->topic,
            ],
            'form' => $form,
            'options' => $this->formOptions($plan->academic_year_id, $plan->subject_id),
            'updateUrl' => route('plans.update', $plan),
            'indexUrl' => route('plans.index'),
            'draftUrl' => route('plans.draft', $plan),
        ]);
    }

    public function update(Request $request, LearningPlan $plan): RedirectResponse
    {
        $this->authorizeOwnerOrAdmin($plan);

        $validated = $this->validatePlanFields($request);
        $status = $request->input('status') === 'published'
            ? PlanStatus::Published
            : PlanStatus::Draft;

        $plan->update([
            ...$validated,
            'student_needs' => $validated['student_needs'] ?: null,
            'status' => $status,
        ]);

        return redirect()->route('plans.edit', $plan)->with('message', 'Rencana pembelajaran berhasil disimpan.');
    }

    public function destroy(LearningPlan $plan): RedirectResponse
    {
        $this->authorizeOwnerOrAdmin($plan);
        $plan->delete();

        return back()->with('message', 'Rencana pembelajaran dihapus.');
    }

    public function openMaterial(LearningPlan $plan): RedirectResponse
    {
        LearningPlan::forCurrentUser()->findOrFail($plan->id);

        $material = $plan->material;
        if (! $material) {
            $material = LearningMaterial::create([
                'plan_id' => $plan->id,
                'status' => MaterialStatus::Draft,
                'content' => [
                    'title' => "Bahan Ajar: {$plan->topic}",
                    'sections' => [
                        [
                            'heading' => "1. Pengenalan {$plan->topic}",
                            'body' => "<p>Isi teks materi pembelajaran untuk topik <strong>{$plan->topic}</strong> di sini.</p>",
                        ],
                    ],
                    'reflectionQuestion' => ["Apa yang kamu pelajari dari {$plan->topic}?"],
                ],
            ]);
        }

        return redirect()->route('materials.edit', $material);
    }

    public function import(Request $request, LearningPlanExportImportService $service): RedirectResponse
    {
        $request->validate([
            'importFile' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'importFile.required' => 'Silakan pilih berkas Excel atau CSV terlebih dahulu.',
            'importFile.mimes' => 'Format berkas harus .xlsx, .xls, atau .csv.',
            'importFile.max' => 'Ukuran berkas maksimal 5MB.',
        ]);

        $res = $service->importPlans($request->file('importFile'), Auth::id());

        if ($res['success']) {
            return redirect()
                ->route('plans.index')
                ->with('message', "Berhasil mengimpor {$res['imported']} draf Rencana Pembelajaran.")
                ->with('importErrors', $res['errors']);
        }

        return redirect()
            ->route('plans.index')
            ->with('importErrors', $res['errors']);
    }

    public function draft(LearningPlan $plan): Response
    {
        abort_unless($plan->teacher_id === Auth::id(), 403);

        $generation = $plan->aiGenerations()->latest()->first();
        $hydrated = $this->hydrateGenerationOutput($generation);

        return Inertia::render('Plans/Draft', [
            'plan' => [
                'id' => $plan->id,
                'topic' => $plan->topic,
                'status' => $plan->status->value ?? 'draft',
                'statusLabel' => $plan->status->label(),
            ],
            'generation' => $generation ? [
                'id' => $generation->id,
                'reviewStatus' => $generation->review_status,
                'model' => $generation->model,
            ] : null,
            'output' => $hydrated,
            'canApprove' => $generation !== null
                && in_array($plan->status, [PlanStatus::Draft, PlanStatus::Reviewed], true),
            'canPublish' => in_array($plan->status, [PlanStatus::Reviewed, PlanStatus::Published], true)
                && $plan->material()->exists(),
            'urls' => [
                'approve' => route('plans.draft.approve', $plan),
                'publish' => route('plans.draft.publish', $plan),
                'index' => route('plans.index'),
            ],
        ]);
    }

    public function approveDraft(Request $request, LearningPlan $plan): RedirectResponse
    {
        abort_unless($plan->teacher_id === Auth::id(), 403);

        $generation = $plan->aiGenerations()->latest()->first();
        if (! $generation) {
            return back()->with('message', 'Tidak ada draf AI untuk disetujui.');
        }

        $hydrated = $this->hydrateGenerationOutput($generation);

        $cpDraft = $request->input('cpDraft', $hydrated['cpDraft']);
        $tpDraft = is_array($request->input('tpDraft')) ? $request->input('tpDraft') : $hydrated['tpDraft'];
        $atpDraft = is_array($request->input('atpDraft')) ? $request->input('atpDraft') : $hydrated['atpDraft'];
        $lessonPlan = is_array($request->input('lessonPlan')) ? $request->input('lessonPlan') : $hydrated['lessonPlan'];
        $materialDraft = is_array($request->input('materialDraft')) ? $request->input('materialDraft') : $hydrated['materialDraft'];
        $reviewNotes = is_array($request->input('reviewNotes')) ? $request->input('reviewNotes') : $hydrated['reviewNotes'];

        $materialContent = [
            'title' => $materialDraft['title'] ?? $plan->topic,
            'sections' => $materialDraft['sections'] ?? [],
            'reflectionQuestion' => $materialDraft['reflectionQuestion'] ?? null,
            'cp' => $cpDraft,
            'tp' => $tpDraft,
            'atp' => $atpDraft,
            'lesson_plan' => $lessonPlan,
        ];

        $plan->material()->updateOrCreate(
            ['plan_id' => $plan->id],
            [
                'content' => $materialContent,
                'status' => MaterialStatus::Draft,
            ]
        );

        $plan->update(['status' => PlanStatus::Reviewed]);

        $generation->update([
            'review_status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
            'output' => array_merge($generation->output ?? [], [
                'cpDraft' => $cpDraft,
                'tpDraft' => $tpDraft,
                'atpDraft' => $atpDraft,
                'lessonPlanDraft' => $lessonPlan,
                'learningMaterialDraft' => $materialDraft,
                'reviewNotes' => $reviewNotes,
            ]),
        ]);

        return back()->with('message', 'Draf berhasil disimpan! Silakan review kembali sebelum diterbitkan.');
    }

    public function publishDraft(LearningPlan $plan): RedirectResponse
    {
        abort_unless($plan->teacher_id === Auth::id(), 403);

        $canPublish = in_array($plan->status, [PlanStatus::Reviewed, PlanStatus::Published], true)
            && $plan->material()->exists();
        abort_unless($canPublish, 403);

        $plan->update(['status' => PlanStatus::Published]);
        $plan->material()->update([
            'status' => MaterialStatus::Published,
            'published_at' => now(),
        ]);

        return back()->with('message', 'Rencana pembelajaran berhasil diterbitkan! Siswa sudah bisa mengakses materi.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePlanFields(Request $request): array
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'semester_id' => 'required|integer|exists:semesters,id',
            'class_id' => 'required|integer|exists:school_classes,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'curriculum_cp_id' => 'nullable|integer|exists:curriculum_cps,id',
            'curriculum_tp_id' => 'nullable|integer|exists:curriculum_tps,id',
            'phase' => 'required|string|max:10',
            'grade' => 'required|integer|min:1|max:12',
            'topic' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:10|max:480',
            'learning_objectives' => 'required|string|max:1000',
            'student_needs' => 'nullable|string|max:500',
            'curriculum_reference' => 'nullable|string|max:2000',
        ]);

        if (! empty($validated['curriculum_tp_id'])) {
            $tp = CurriculumTp::with('cp')->find($validated['curriculum_tp_id']);
            if ($tp) {
                $validated['curriculum_cp_id'] = $tp->curriculum_cp_id;
                if ($tp->grade) {
                    $validated['grade'] = (int) $tp->grade;
                }
                if ($tp->cp?->phase) {
                    $validated['phase'] = $tp->cp->phase;
                }
                if (trim((string) ($validated['curriculum_reference'] ?? '')) === '') {
                    $validated['curriculum_reference'] = implode(' — ', [
                        $tp->cp?->label() ?? 'CP',
                        "TP {$tp->code}",
                        $tp->statement,
                    ]);
                }
                if (trim((string) ($validated['learning_objectives'] ?? '')) === '') {
                    $validated['learning_objectives'] = $tp->statement;
                }
            }
        }

        if (trim((string) ($validated['curriculum_reference'] ?? '')) === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'curriculum_reference' => 'Referensi kurikulum wajib diisi.',
            ]);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function createPlan(array $validated): LearningPlan
    {
        return LearningPlan::create([
            'teacher_id' => Auth::id(),
            'academic_year_id' => $validated['academic_year_id'],
            'semester_id' => $validated['semester_id'],
            'class_id' => $validated['class_id'],
            'subject_id' => $validated['subject_id'],
            'curriculum_cp_id' => $validated['curriculum_cp_id'] ?? null,
            'curriculum_tp_id' => $validated['curriculum_tp_id'] ?? null,
            'phase' => $validated['phase'],
            'grade' => $validated['grade'],
            'topic' => $validated['topic'],
            'duration_minutes' => $validated['duration_minutes'],
            'learning_objectives' => $validated['learning_objectives'],
            'student_needs' => ($validated['student_needs'] ?? null) ?: null,
            'curriculum_reference' => $validated['curriculum_reference'],
            'status' => PlanStatus::Draft,
        ]);
    }

    /**
     * @return array{academic_year_id: int, semester_id: int, class_id: int, subject_id: int, phase: string, grade: int, topic: string, duration_minutes: int, learning_objectives: string, student_needs: string, curriculum_reference: string, curriculum_cp_id: null, curriculum_tp_id: null}
     */
    private function resolveCreateDefaults(): array
    {
        $academicYearId = AcademicYear::active()?->id
            ?? AcademicYear::query()->value('id')
            ?? 0;

        $semesterId = $this->resolveDefaultSemesterId((int) $academicYearId);

        $subjectId = 0;
        $phase = 'D';
        $inf = Subject::query()->where('code', 'INF')->first();
        if ($inf) {
            $subjectId = $inf->id;
            $phase = $inf->phase ?: 'D';
        }

        $class = SchoolClass::query()
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->orderBy('name')
            ->first();

        return [
            'academic_year_id' => (int) $academicYearId,
            'semester_id' => (int) $semesterId,
            'class_id' => (int) ($class?->id ?? 0),
            'subject_id' => (int) $subjectId,
            'curriculum_cp_id' => null,
            'curriculum_tp_id' => null,
            'phase' => $phase,
            'grade' => (int) ($class?->grade ?? 7),
            'topic' => '',
            'duration_minutes' => 80,
            'learning_objectives' => '',
            'student_needs' => '',
            'curriculum_reference' => '',
        ];
    }

    private function resolveDefaultSemesterId(int $academicYearId): int
    {
        if (! $academicYearId) {
            return (int) (Semester::active()?->id ?? 0);
        }

        return (int) (Semester::query()
            ->where('academic_year_id', $academicYearId)
            ->orderByDesc('is_active')
            ->orderBy('number')
            ->value('id') ?? 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(int $academicYearId = 0, int $subjectId = 0): array
    {
        $user = Auth::user();

        return [
            'academicYears' => AcademicYear::query()
                ->orderByDesc('is_active')
                ->orderByDesc('starts_on')
                ->get(['id', 'name', 'is_active']),
            'semesters' => Semester::query()
                ->orderBy('academic_year_id')
                ->orderBy('number')
                ->get(['id', 'name', 'academic_year_id', 'number', 'is_active']),
            'classes' => SchoolClass::query()
                ->with('academicYear:id,name')
                ->orderBy('name')
                ->get()
                ->map(fn (SchoolClass $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'grade' => $c->grade,
                    'academic_year_id' => $c->academic_year_id,
                ]),
            'subjects' => $user?->isAdmin()
                ? Subject::query()->orderBy('name')->get(['id', 'name', 'code', 'phase'])
                : $user?->taughtSubjects()->orderBy('name')->get(['subjects.id', 'subjects.name', 'subjects.code', 'subjects.phase']),
            'cps' => CurriculumCp::query()
                ->orderBy('sequence')
                ->get()
                ->map(fn (CurriculumCp $cp) => [
                    'id' => $cp->id,
                    'label' => $cp->label(),
                    'subject_id' => $cp->subject_id,
                    'phase' => $cp->phase,
                    'statement' => $cp->statement,
                ]),
            'tps' => CurriculumTp::query()
                ->orderBy('code')
                ->get()
                ->map(fn (CurriculumTp $tp) => [
                    'id' => $tp->id,
                    'label' => $tp->label(),
                    'cp_id' => $tp->curriculum_cp_id,
                    'grade' => $tp->grade,
                    'statement' => $tp->statement,
                ]),
            'phases' => ['A', 'B', 'C', 'D', 'E', 'F'],
        ];
    }

    /**
     * @return array{cpDraft: string, tpDraft: array, atpDraft: array, lessonPlan: array, materialDraft: array, reviewNotes: array}
     */
    private function hydrateGenerationOutput(?AiGeneration $generation): array
    {
        $cpDraft = '';
        $tpDraft = [];
        $atpDraft = [];
        $lessonPlan = [];
        $materialDraft = [];
        $reviewNotes = [];

        if ($generation?->output) {
            $out = $generation->output;
            $rawCp = $out['cpDraft'] ?? '';
            if (is_array($rawCp)) {
                $cpDraft = isset($rawCp['statement'])
                    ? (string) $rawCp['statement']
                    : implode("\n", array_filter(array_map(
                        fn ($v) => is_string($v) ? $v : json_encode($v),
                        $rawCp
                    )));
            } else {
                $cpDraft = (string) $rawCp;
            }

            $tpDraft = is_array($out['tpDraft'] ?? null) ? $out['tpDraft'] : [];
            $atpDraft = is_array($out['atpDraft'] ?? null) ? $out['atpDraft'] : [];
            $lessonPlan = is_array($out['lessonPlanDraft'] ?? null) ? $out['lessonPlanDraft'] : [];
            $materialDraft = is_array($out['learningMaterialDraft'] ?? null) ? $out['learningMaterialDraft'] : [];
            $reviewNotes = is_array($out['reviewNotes'] ?? null) ? $out['reviewNotes'] : [];
        }

        return compact('cpDraft', 'tpDraft', 'atpDraft', 'lessonPlan', 'materialDraft', 'reviewNotes');
    }

    private function authorizeOwnerOrAdmin(LearningPlan $plan): void
    {
        $user = Auth::user();
        if (! $user->isAdmin() && $plan->teacher_id !== $user->id) {
            abort(403, 'Anda tidak memiliki hak untuk mengedit rencana pembelajaran ini.');
        }
    }
}
