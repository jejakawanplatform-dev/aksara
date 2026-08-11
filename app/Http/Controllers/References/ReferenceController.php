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

namespace App\Http\Controllers\References;

use App\Enums\PlanStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\CurriculumAtpItem;
use App\Models\CurriculumCp;
use App\Models\CurriculumTp;
use App\Models\LearningPlan;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use App\Services\SettingService;
use App\Support\Access\PermissionCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ReferenceController extends Controller
{
    /** @var list<string> */
    private const TABS = ['profil', 'operasional', 'tahun', 'semester', 'rombel', 'mapel', 'cp', 'atp'];

    /** Tab yang diizinkan per section sub-menu. */
    private const SECTION_CONFIG = [
        'references.section.school' => [
            'tabs'       => ['profil', 'operasional'],
            'defaultTab' => 'profil',
            'pageTitle'  => 'Profil Sekolah',
        ],
        'references.section.academic' => [
            'tabs'       => ['tahun', 'semester', 'rombel', 'mapel'],
            'defaultTab' => 'tahun',
            'pageTitle'  => 'Data Akademik',
        ],
        'references.section.curriculum' => [
            'tabs'       => ['cp', 'atp'],
            'defaultTab' => 'cp',
            'pageTitle'  => 'Kurikulum',
        ],
    ];

    public function index(Request $request, SettingService $service): Response
    {
        $user = Auth::user();
        abort_unless($user && $user->can(PermissionCatalog::REFERENCES_VIEW), 403);

        $canManage = $user->can(PermissionCatalog::REFERENCES_MANAGE);

        $routeName   = $request->route()?->getName() ?? 'references.index';
        $sectionConf = self::SECTION_CONFIG[$routeName] ?? null;
        $allowedTabs = $sectionConf ? $sectionConf['tabs'] : self::TABS;
        $pageTitle   = $sectionConf ? $sectionConf['pageTitle'] : 'Referensi Master';

        $sectionDefaultTab = $sectionConf
            ? ($canManage ? $sectionConf['defaultTab'] : ($sectionConf['defaultTab'] === 'profil' ? 'operasional' : $sectionConf['defaultTab']))
            : ($canManage ? 'profil' : 'tahun');

        $tab = (string) $request->query('tab', $sectionDefaultTab);
        if (! in_array($tab, $allowedTabs, true)) {
            $tab = $sectionDefaultTab;
        }
        if (in_array($tab, ['profil', 'operasional'], true) && ! $canManage) {
            $tab = $allowedTabs[array_key_first(array_filter($allowedTabs, fn ($t) => ! in_array($t, ['profil', 'operasional'], true)))] ?? 'tahun';
        }

        $defaultMapelScope = $canManage ? 'all' : 'my';
        $mapelScopeFilter = (string) $request->query('mapelScope', $defaultMapelScope);
        if (! in_array($mapelScopeFilter, ['my', 'all'], true)) {
            $mapelScopeFilter = $defaultMapelScope;
        }

        $subjectId = $request->query('subjectId');
        if ($subjectId === null || $subjectId === '') {
            $subjectId = Subject::query()->where('code', 'INF')->value('id')
                ?? Subject::query()->value('id');
        } else {
            $subjectId = (int) $subjectId;
        }

        $atpGradeFilter = $request->query('atpGradeFilter', 7);
        if ($atpGradeFilter === '' || $atpGradeFilter === null) {
            $atpGradeFilter = null;
        } else {
            $atpGradeFilter = (int) $atpGradeFilter;
        }

        $perPage = (int) $request->query('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $membersClassId = $request->query('membersClassId')
            ? (int) $request->query('membersClassId')
            : null;

        $mySubjectIds = Auth::check()
            ? DB::table('subject_teachers')->where('teacher_id', Auth::id())->pluck('subject_id')->unique()->values()->all()
            : [];

        if (empty($mySubjectIds) && Auth::check()) {
            $mySubjectIds = LearningPlan::where('teacher_id', Auth::id())->pluck('subject_id')->unique()->values()->all();
        }
        if (empty($mySubjectIds) && Subject::where('code', 'INF')->exists()) {
            $mySubjectIds = [Subject::where('code', 'INF')->value('id')];
        }

        /** Opsi mapel untuk filter/dropdown (selalu penuh, ringan) */
        $subjectOptions = Subject::query()->orderBy('name')->get(['id', 'name', 'code']);

        $subjectsQuery = Subject::query()->with('teachers')->orderBy('name');
        if (! $canManage && $mapelScopeFilter === 'my' && ! empty($mySubjectIds)) {
            $subjectsQuery->whereIn('id', $mySubjectIds);
        }
        $subjects = $subjectsQuery
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Subject $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'code' => $s->code,
                'phase' => $s->phase,
                'jenjang' => $s->jenjang,
                'description' => $s->description,
                'teacherIds' => $s->teachers->pluck('id')->values(),
                'teacherNames' => $s->teachers->pluck('name')->values(),
            ]);

        $years = AcademicYear::query()->with('semesters')->orderByDesc('starts_on')->get()->map(fn (AcademicYear $y) => [
            'id' => $y->id,
            'name' => $y->name,
            'code' => $y->code,
            'starts_on' => optional($y->starts_on)?->format('Y-m-d'),
            'ends_on' => optional($y->ends_on)?->format('Y-m-d'),
            'is_active' => (bool) $y->is_active,
        ]);

        $semesters = Semester::query()
            ->with('academicYear')
            ->orderByDesc('academic_year_id')
            ->orderBy('number')
            ->get()
            ->map(fn (Semester $s) => [
                'id' => $s->id,
                'academic_year_id' => $s->academic_year_id,
                'yearName' => $s->academicYear?->name,
                'name' => $s->name,
                'code' => $s->code,
                'number' => $s->number,
                'starts_on' => optional($s->starts_on)?->format('Y-m-d'),
                'ends_on' => optional($s->ends_on)?->format('Y-m-d'),
                'is_active' => (bool) $s->is_active,
            ]);

        $rombels = SchoolClass::query()
            ->with(['academicYear', 'homeroomTeacher'])
            ->withCount('students')
            ->orderBy('grade')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (SchoolClass $c) => [
                'id' => $c->id,
                'academic_year_id' => $c->academic_year_id,
                'yearName' => $c->academicYear?->name,
                'name' => $c->name,
                'rombel_code' => $c->rombel_code,
                'grade' => $c->grade,
                'homeroom_teacher_id' => $c->homeroom_teacher_id,
                'homeroomName' => $c->homeroomTeacher?->name,
                'students_count' => $c->students_count,
            ]);

        $cps = CurriculumCp::query()
            ->with(['tps' => fn ($q) => $q->orderBy('sequence')])
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->orderBy('sequence')
            ->get()
            ->map(fn (CurriculumCp $cp) => [
                'id' => $cp->id,
                'subject_id' => $cp->subject_id,
                'phase' => $cp->phase,
                'element_code' => $cp->element_code,
                'element_name' => $cp->element_name,
                'statement' => $cp->statement,
                'source_note' => $cp->source_note,
                'sequence' => $cp->sequence,
                'tps' => $cp->tps->map(fn (CurriculumTp $tp) => [
                    'id' => $tp->id,
                    'code' => $tp->code,
                    'statement' => $tp->statement,
                    'grade' => $tp->grade,
                    'sequence' => $tp->sequence,
                    'curriculum_cp_id' => $tp->curriculum_cp_id,
                ])->values()->all(),
            ]);

        $atp = CurriculumAtpItem::query()
            ->with(['tp.cp', 'academicYear', 'semester'])
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->when($atpGradeFilter !== null, fn ($q) => $q->where('grade', $atpGradeFilter))
            ->orderBy('grade')
            ->orderBy('sequence')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (CurriculumAtpItem $item) => [
                'id' => $item->id,
                'subject_id' => $item->subject_id,
                'academic_year_id' => $item->academic_year_id,
                'yearName' => $item->academicYear?->name,
                'semester_id' => $item->semester_id,
                'semesterName' => $item->semester?->name,
                'curriculum_tp_id' => $item->curriculum_tp_id,
                'tpCode' => $item->tp?->code,
                'grade' => $item->grade,
                'sequence' => $item->sequence,
                'unit_title' => $item->unit_title,
                'estimated_meetings' => $item->estimated_meetings,
            ]);

        $tpOptions = CurriculumTp::query()
            ->with('cp')
            ->when($subjectId, fn ($q) => $q->whereHas('cp', fn ($cq) => $cq->where('subject_id', $subjectId)))
            ->orderBy('code')
            ->get()
            ->map(fn (CurriculumTp $tp) => [
                'id' => $tp->id,
                'code' => $tp->code,
                'grade' => $tp->grade,
                'statement' => $tp->statement,
            ]);

        $memberClass = null;
        $availableStudents = [];
        if ($membersClassId) {
            $mc = SchoolClass::query()->with(['students' => fn ($q) => $q->orderBy('name')])->find($membersClassId);
            if ($mc) {
                $memberClass = [
                    'id' => $mc->id,
                    'name' => $mc->name,
                    'students' => $mc->students->map(fn ($s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                    ])->values(),
                ];
                $memberIds = $mc->students->pluck('id');
                $availableStudents = User::query()
                    ->where('role', UserRole::Student)
                    ->when($memberIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $memberIds))
                    ->orderBy('name')
                    ->get(['id', 'name']);
            }
        }

        $teacherEnrolledClassIds = Auth::check()
            ? LearningPlan::where('teacher_id', Auth::id())->pluck('class_id')->unique()->values()->all()
            : [];

        $allTabs = [
            ['key' => 'profil',     'label' => 'Profil Sekolah',  'adminOnly' => true],
            ['key' => 'operasional','label' => 'Operasional',     'adminOnly' => true],
            ['key' => 'tahun',     'label' => 'Tahun Ajaran',     'adminOnly' => false],
            ['key' => 'semester',  'label' => 'Semester',         'adminOnly' => false],
            ['key' => 'rombel',    'label' => 'Rombel',           'adminOnly' => false],
            ['key' => 'mapel',     'label' => 'Mata Pelajaran',   'adminOnly' => false],
            ['key' => 'cp',        'label' => 'CP & TP',          'adminOnly' => false],
            ['key' => 'atp',       'label' => 'ATP',              'adminOnly' => false],
        ];
        $tabs = array_values(array_filter($allTabs, fn ($t) => in_array($t['key'], $allowedTabs, true)));

        return Inertia::render('References/Index', [
            'pageTitle' => $pageTitle,
            'tab' => $tab,
            'tabs' => $tabs,
            'canManage' => $canManage,
            'canManageCurrentSubject' => $this->canManageSubject($subjectId ? (int) $subjectId : null),
            'filters' => [
                'subjectId' => $subjectId,
                'atpGradeFilter' => $atpGradeFilter,
                'mapelScope' => $mapelScopeFilter,
                'membersClassId' => $membersClassId,
                'per_page' => $perPage,
            ],
            'school' => [
                'name' => (string) $service->get('school.name', 'SMP Negeri 1 Aksara'),
                'npsn' => (string) $service->get('school.npsn', '12345678'),
                'address' => (string) $service->get('school.address', 'Jl. Pendidikan No. 1, Jakarta'),
                'headmaster' => (string) $service->get('school.headmaster', 'Drs. H. Mulyadi, M.Pd.'),
                'phone' => (string) $service->get('school.phone', '021-5551234'),
            ],
            'academic' => [
                'passing_score' => (int) $service->get('academic.passing_score', 70),
                'quiz_attempt_limit' => (int) $service->get('academic.quiz_attempt_limit', 1),
                'attendance_tolerance_minutes' => (int) $service->get('academic.attendance_tolerance_minutes', 15),
            ],
            'years' => $years,
            'semesters' => $semesters,
            'rombels' => $rombels,
            'subjects' => $subjects,
            'subjectOptions' => $subjectOptions,
            'cps' => $cps,
            'atp' => $atp,
            'tpOptions' => $tpOptions,
            'homeroomCandidates' => User::query()
                ->whereIn('role', [UserRole::HomeroomTeacher, UserRole::Teacher])
                ->orderBy('name')
                ->get(['id', 'name']),
            'allTeachers' => User::query()
                ->whereIn('role', [UserRole::Teacher, UserRole::HomeroomTeacher, UserRole::Admin])
                ->orderBy('name')
                ->get(['id', 'name']),
            'memberClass' => $memberClass,
            'availableStudents' => $availableStudents,
            'teacherEnrolledClassIds' => $teacherEnrolledClassIds,
            'mySubjectIds' => $mySubjectIds,
            'isTeacher' => $user->isTeacher() || $user->isHomeroomTeacher(),
            'urls' => [
                'index' => route($routeName),
                'school' => route('references.school'),
                'academic' => route('references.academic'),
                'yearsStore' => route('references.years.store'),
                'yearsUpdate' => route('references.years.update', ['year' => '__ID__']),
                'yearsDestroy' => route('references.years.destroy', ['year' => '__ID__']),
                'semestersStore' => route('references.semesters.store'),
                'semestersUpdate' => route('references.semesters.update', ['semester' => '__ID__']),
                'semestersDestroy' => route('references.semesters.destroy', ['semester' => '__ID__']),
                'semestersActivate' => route('references.semesters.activate', ['semester' => '__ID__']),
                'rombelsStore' => route('references.rombels.store'),
                'rombelsUpdate' => route('references.rombels.update', ['rombel' => '__ID__']),
                'rombelsDestroy' => route('references.rombels.destroy', ['rombel' => '__ID__']),
                'rombelsAttachStudent' => route('references.rombels.attach-student', ['rombel' => '__ID__']),
                'rombelsDetachStudent' => route('references.rombels.detach-student', ['rombel' => '__RID__', 'student' => '__SID__']),
                'rombelsEnrol' => route('references.rombels.enrol', ['rombel' => '__ID__']),
                'mapelStore' => route('references.mapel.store'),
                'mapelUpdate' => route('references.mapel.update', ['subject' => '__ID__']),
                'mapelDestroy' => route('references.mapel.destroy', ['subject' => '__ID__']),
                'mapelTeachers' => route('references.mapel.teachers', ['subject' => '__ID__']),
                'cpsStore' => route('references.cps.store'),
                'cpsUpdate' => route('references.cps.update', ['cp' => '__ID__']),
                'cpsDestroy' => route('references.cps.destroy', ['cp' => '__ID__']),
                'tpsStore' => route('references.tps.store'),
                'tpsUpdate' => route('references.tps.update', ['tp' => '__ID__']),
                'tpsDestroy' => route('references.tps.destroy', ['tp' => '__ID__']),
                'atpStore' => route('references.atp.store'),
                'atpUpdate' => route('references.atp.update', ['atp' => '__ID__']),
                'atpDestroy' => route('references.atp.destroy', ['atp' => '__ID__']),
                'importCpTp' => route('references.import.cp-tp'),
                'importAtp' => route('references.import.atp'),
                'exportCpTp' => route('references.export.cp-tp', ['subject' => '__ID__', 'format' => '__FMT__']),
                'exportAtp' => route('references.export.atp', ['subject' => '__ID__', 'format' => '__FMT__']),
            ],
        ]);
    }

    public function saveSchoolProfile(Request $request, SettingService $service): RedirectResponse
    {
        $this->ensureCanManage();

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'npsn' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'headmaster' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $service->set('school.name', $data['name'] ?? '', 'string', 'school', 'Nama Sekolah / Instansi');
        $service->set('school.npsn', $data['npsn'] ?? '', 'string', 'school', 'NPSN');
        $service->set('school.address', $data['address'] ?? '', 'string', 'school', 'Alamat Sekolah');
        $service->set('school.headmaster', $data['headmaster'] ?? '', 'string', 'school', 'Nama Kepala Sekolah');
        $service->set('school.phone', $data['phone'] ?? '', 'string', 'school', 'No. Telepon Sekolah');

        return redirect()->route('references.index', ['tab' => 'profil'])
            ->with('message', 'Profil sekolah berhasil disimpan.');
    }

    public function saveAcademicOps(Request $request, SettingService $service): RedirectResponse
    {
        $this->ensureCanManage();

        $data = $request->validate([
            'passing_score' => 'required|integer|min:0|max:100',
            'quiz_attempt_limit' => 'required|integer|min:1|max:20',
            'attendance_tolerance_minutes' => 'required|integer|min:0|max:120',
        ]);

        $service->set('academic.passing_score', $data['passing_score'], 'integer', 'academic', 'Nilai Kelulusan KKM Kuis');
        $service->set('academic.quiz_attempt_limit', $data['quiz_attempt_limit'], 'integer', 'academic', 'Batas Percobaan Kuis');
        $service->set('academic.attendance_tolerance_minutes', $data['attendance_tolerance_minutes'], 'integer', 'academic', 'Toleransi Menit Keterlambatan');

        return redirect()->route('references.index', ['tab' => 'operasional'])
            ->with('message', 'Pengaturan operasional akademik berhasil disimpan.');
    }

    public function storeYear(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $data = $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:academic_years,code',
            'starts_on' => 'nullable|date',
            'ends_on' => 'nullable|date|after_or_equal:starts_on',
            'is_active' => 'boolean',
        ]);

        if ($data['is_active'] ?? false) {
            AcademicYear::query()->update(['is_active' => false]);
        }

        AcademicYear::query()->create([
            'name' => $data['name'],
            'code' => $data['code'],
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ]);

        return redirect()->route('references.index', ['tab' => 'tahun'])
            ->with('message', 'Tahun ajaran ditambahkan.');
    }

    public function updateYear(Request $request, AcademicYear $year): RedirectResponse
    {
        $this->ensureCanManage();

        $data = $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:academic_years,code,'.$year->id,
            'starts_on' => 'nullable|date',
            'ends_on' => 'nullable|date|after_or_equal:starts_on',
            'is_active' => 'boolean',
        ]);

        if ($data['is_active'] ?? false) {
            AcademicYear::query()->update(['is_active' => false]);
        }

        $year->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ]);

        return redirect()->route('references.index', ['tab' => 'tahun'])
            ->with('message', 'Tahun ajaran diperbarui.');
    }

    public function destroyYear(AcademicYear $year): RedirectResponse
    {
        $this->ensureCanManage();

        if ($year->semesters()->exists() || $year->classes()->exists() || $year->learningPlans()->exists()) {
            return back()->with('error', 'Tahun ajaran masih punya semester, rombel, atau rencana — hapus/pindahkan dulu.');
        }

        $year->delete();

        return redirect()->route('references.index', ['tab' => 'tahun'])
            ->with('message', 'Tahun ajaran dihapus.');
    }

    public function storeSemester(Request $request): RedirectResponse
    {
        $this->ensureCanManage();
        $this->persistSemester($request);

        return redirect()->route('references.index', ['tab' => 'semester'])
            ->with('message', 'Semester ditambahkan.');
    }

    public function updateSemester(Request $request, Semester $semester): RedirectResponse
    {
        $this->ensureCanManage();
        $this->persistSemester($request, $semester);

        return redirect()->route('references.index', ['tab' => 'semester'])
            ->with('message', 'Semester diperbarui.');
    }

    public function destroySemester(Semester $semester): RedirectResponse
    {
        $this->ensureCanManage();

        if ($semester->learningPlans()->exists() || $semester->atpItems()->exists()) {
            return back()->with('error', 'Semester masih dipakai rencana atau ATP.');
        }

        $semester->delete();

        return redirect()->route('references.index', ['tab' => 'semester'])
            ->with('message', 'Semester dihapus.');
    }

    public function activateSemester(Semester $semester): RedirectResponse
    {
        $this->ensureCanManage();

        Semester::query()->where('academic_year_id', $semester->academic_year_id)->update(['is_active' => false]);
        $semester->update(['is_active' => true]);

        return redirect()->route('references.index', ['tab' => 'semester'])
            ->with('message', "Semester {$semester->name} diaktifkan.");
    }

    public function storeRombel(Request $request): RedirectResponse
    {
        $this->ensureCanManage();
        $this->persistRombel($request);

        return redirect()->route('references.index', ['tab' => 'rombel'])
            ->with('message', 'Rombel ditambahkan.');
    }

    public function updateRombel(Request $request, SchoolClass $rombel): RedirectResponse
    {
        $this->ensureCanManage();
        $this->persistRombel($request, $rombel);

        return redirect()->route('references.index', ['tab' => 'rombel'])
            ->with('message', 'Rombel diperbarui.');
    }

    public function destroyRombel(SchoolClass $rombel): RedirectResponse
    {
        $this->ensureCanManage();

        if ($rombel->learningPlans()->exists()) {
            return back()->with('error', 'Rombel masih punya rencana pembelajaran.');
        }

        $rombel->students()->detach();
        $rombel->delete();

        return redirect()->route('references.index', ['tab' => 'rombel'])
            ->with('message', 'Rombel dihapus.');
    }

    public function attachStudent(Request $request, SchoolClass $rombel): RedirectResponse
    {
        $this->ensureCanManage();

        $data = $request->validate([
            'student_id' => 'required|exists:users,id',
        ]);

        $rombel->students()->syncWithoutDetaching([$data['student_id']]);

        return redirect()->route('references.index', ['tab' => 'rombel', 'membersClassId' => $rombel->id])
            ->with('message', 'Siswa ditambahkan ke rombel.');
    }

    public function detachStudent(SchoolClass $rombel, User $student): RedirectResponse
    {
        $this->ensureCanManage();

        $rombel->students()->detach($student->id);

        return redirect()->route('references.index', ['tab' => 'rombel', 'membersClassId' => $rombel->id])
            ->with('message', 'Siswa dikeluarkan dari rombel.');
    }

    public function toggleTeacherEnrolment(SchoolClass $rombel): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isTeacher() && ! $user->isHomeroomTeacher())) {
            return back();
        }

        $existingPlan = LearningPlan::where('teacher_id', $user->id)
            ->where('class_id', $rombel->id)
            ->first();

        if ($existingPlan) {
            if ($existingPlan->isPublished()) {
                return back()->with('error', 'Kelas tidak dapat dibatalkan enrolment karena sudah memiliki rencana pembelajaran yang diterbitkan.');
            }
            $existingPlan->delete();

            return redirect()->route('references.index', ['tab' => 'rombel'])
                ->with('message', 'Berhasil membatalkan enrolment kelas ajar.');
        }

        $subject = Subject::where('code', 'INF')->first() ?? Subject::first();
        $activeYear = AcademicYear::active() ?? AcademicYear::first();
        $activeSemester = Semester::active() ?? Semester::first();

        if (! $subject || ! $activeYear) {
            return back()->with('error', 'Mata pelajaran atau tahun ajaran aktif belum tersedia.');
        }

        LearningPlan::create([
            'teacher_id' => $user->id,
            'academic_year_id' => $activeYear->id,
            'semester_id' => $activeSemester?->id,
            'class_id' => $rombel->id,
            'subject_id' => $subject->id,
            'phase' => 'D',
            'grade' => $rombel->grade ?? 7,
            'topic' => 'Draf Rencana Pembelajaran Ajar Kelas',
            'duration_minutes' => 80,
            'learning_objectives' => 'Mempelajari konsep dasar kurikulum.',
            'curriculum_reference' => 'Kurikulum Merdeka',
            'status' => PlanStatus::Draft,
        ]);

        return redirect()->route('references.index', ['tab' => 'rombel'])
            ->with('message', 'Berhasil mendaftarkan (enrol) kelas ajar baru!');
    }

    public function storeMapel(Request $request): RedirectResponse
    {
        $this->ensureCanManage();
        $this->persistMapel($request);

        return redirect()->route('references.index', ['tab' => 'mapel'])
            ->with('message', 'Mata pelajaran ditambahkan.');
    }

    public function updateMapel(Request $request, Subject $subject): RedirectResponse
    {
        $this->ensureCanManage();
        $this->persistMapel($request, $subject);

        return redirect()->route('references.index', ['tab' => 'mapel'])
            ->with('message', 'Mata pelajaran diperbarui.');
    }

    public function destroyMapel(Subject $subject): RedirectResponse
    {
        $this->ensureCanManage();

        if ($subject->cps()->exists() || $subject->atpItems()->exists() || $subject->learningPlans()->exists()) {
            return back()->with('error', 'Mata pelajaran masih punya CP, ATP, atau rencana pembelajaran.');
        }

        $subject->delete();

        return redirect()->route('references.index', ['tab' => 'mapel'])
            ->with('message', 'Mata pelajaran dihapus.');
    }

    public function saveSubjectTeachers(Request $request, Subject $subject): RedirectResponse
    {
        $this->ensureCanManage();

        $data = $request->validate([
            'teacher_ids' => 'array',
            'teacher_ids.*' => 'integer|exists:users,id',
        ]);

        $subject->teachers()->sync(array_filter($data['teacher_ids'] ?? []));

        return redirect()->route('references.index', ['tab' => 'mapel'])
            ->with('message', 'Plotting guru pengampu mata pelajaran berhasil disimpan.');
    }

    public function storeCp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject_id' => 'required|integer|exists:subjects,id',
            'phase' => 'required|string|max:5',
            'element_code' => 'required|string|max:30',
            'element_name' => 'required|string|max:100',
            'statement' => 'required|string',
            'source_note' => 'nullable|string|max:255',
            'sequence' => 'required|integer|min:1',
        ]);

        abort_unless($this->canManageSubject((int) $data['subject_id']), 403);

        CurriculumCp::query()->create([
            'subject_id' => $data['subject_id'],
            'phase' => $data['phase'],
            'element_code' => strtoupper($data['element_code']),
            'element_name' => $data['element_name'],
            'statement' => $data['statement'],
            'source_note' => ($data['source_note'] ?? null) ?: null,
            'sequence' => $data['sequence'],
        ]);

        return redirect()->route('references.index', ['tab' => 'cp', 'subjectId' => $data['subject_id']])
            ->with('message', 'CP ditambahkan.');
    }

    public function updateCp(Request $request, CurriculumCp $cp): RedirectResponse
    {
        $data = $request->validate([
            'subject_id' => 'required|integer|exists:subjects,id',
            'phase' => 'required|string|max:5',
            'element_code' => 'required|string|max:30',
            'element_name' => 'required|string|max:100',
            'statement' => 'required|string',
            'source_note' => 'nullable|string|max:255',
            'sequence' => 'required|integer|min:1',
        ]);

        abort_unless($this->canManageSubject((int) $data['subject_id']), 403);

        $cp->update([
            'subject_id' => $data['subject_id'],
            'phase' => $data['phase'],
            'element_code' => strtoupper($data['element_code']),
            'element_name' => $data['element_name'],
            'statement' => $data['statement'],
            'source_note' => ($data['source_note'] ?? null) ?: null,
            'sequence' => $data['sequence'],
        ]);

        return redirect()->route('references.index', ['tab' => 'cp', 'subjectId' => $data['subject_id']])
            ->with('message', 'CP diperbarui.');
    }

    public function destroyCp(CurriculumCp $cp): RedirectResponse
    {
        abort_unless($this->canManageSubject($cp->subject_id), 403);

        if ($cp->tps()->exists() || $cp->learningPlans()->exists()) {
            return back()->with('error', 'CP masih punya TP atau rencana pembelajaran.');
        }

        $subjectId = $cp->subject_id;
        $cp->delete();

        return redirect()->route('references.index', ['tab' => 'cp', 'subjectId' => $subjectId])
            ->with('message', 'CP dihapus.');
    }

    public function storeTp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'curriculum_cp_id' => 'required|integer|exists:curriculum_cps,id',
            'code' => 'required|string|max:40',
            'statement' => 'required|string',
            'grade' => 'nullable|integer|min:1|max:12',
            'sequence' => 'required|integer|min:1',
        ]);

        $cp = CurriculumCp::findOrFail($data['curriculum_cp_id']);
        abort_unless($this->canManageSubject($cp->subject_id), 403);

        CurriculumTp::query()->create([
            'curriculum_cp_id' => $data['curriculum_cp_id'],
            'code' => $data['code'],
            'statement' => $data['statement'],
            'grade' => $data['grade'] ?: null,
            'sequence' => $data['sequence'],
        ]);

        return redirect()->route('references.index', ['tab' => 'cp', 'subjectId' => $cp->subject_id])
            ->with('message', 'TP ditambahkan.');
    }

    public function updateTp(Request $request, CurriculumTp $tp): RedirectResponse
    {
        $data = $request->validate([
            'curriculum_cp_id' => 'required|integer|exists:curriculum_cps,id',
            'code' => 'required|string|max:40',
            'statement' => 'required|string',
            'grade' => 'nullable|integer|min:1|max:12',
            'sequence' => 'required|integer|min:1',
        ]);

        $cp = CurriculumCp::findOrFail($data['curriculum_cp_id']);
        abort_unless($this->canManageSubject($cp->subject_id), 403);

        $tp->update([
            'curriculum_cp_id' => $data['curriculum_cp_id'],
            'code' => $data['code'],
            'statement' => $data['statement'],
            'grade' => $data['grade'] ?: null,
            'sequence' => $data['sequence'],
        ]);

        return redirect()->route('references.index', ['tab' => 'cp', 'subjectId' => $cp->subject_id])
            ->with('message', 'TP diperbarui.');
    }

    public function destroyTp(CurriculumTp $tp): RedirectResponse
    {
        $tp->load('cp');
        abort_unless($tp->cp && $this->canManageSubject($tp->cp->subject_id), 403);

        if ($tp->learningPlans()->exists() || $tp->atpItems()->exists()) {
            return back()->with('error', 'TP masih dipakai rencana atau ATP.');
        }

        $subjectId = $tp->cp->subject_id;
        $tp->delete();

        return redirect()->route('references.index', ['tab' => 'cp', 'subjectId' => $subjectId])
            ->with('message', 'TP dihapus.');
    }

    public function storeAtp(Request $request): RedirectResponse
    {
        return $this->persistAtp($request);
    }

    public function updateAtp(Request $request, CurriculumAtpItem $atp): RedirectResponse
    {
        return $this->persistAtp($request, $atp);
    }

    public function destroyAtp(CurriculumAtpItem $atp): RedirectResponse
    {
        abort_unless($this->canManageSubject($atp->subject_id), 403);

        $subjectId = $atp->subject_id;
        $atp->delete();

        return redirect()->route('references.index', ['tab' => 'atp', 'subjectId' => $subjectId])
            ->with('message', 'Item ATP dihapus.');
    }

    private function persistAtp(Request $request, ?CurriculumAtpItem $atp = null): RedirectResponse
    {
        $data = $request->validate([
            'subject_id' => 'required|integer|exists:subjects,id',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'semester_id' => 'nullable|integer|exists:semesters,id',
            'curriculum_tp_id' => 'required|integer|exists:curriculum_tps,id',
            'grade' => 'required|integer|min:1|max:12',
            'sequence' => 'required|integer|min:1',
            'unit_title' => 'nullable|string|max:150',
            'estimated_meetings' => 'nullable|integer|min:1',
        ]);

        abort_unless($this->canManageSubject((int) $data['subject_id']), 403);

        $payload = [
            'subject_id' => $data['subject_id'],
            'academic_year_id' => $data['academic_year_id'],
            'semester_id' => $data['semester_id'] ?: null,
            'curriculum_tp_id' => $data['curriculum_tp_id'],
            'grade' => $data['grade'],
            'sequence' => $data['sequence'],
            'unit_title' => $data['unit_title'] ?: null,
            'estimated_meetings' => $data['estimated_meetings'] ?? null,
        ];

        try {
            if ($atp) {
                $atp->update($payload);
                $message = 'Item ATP diperbarui.';
            } else {
                CurriculumAtpItem::query()->create($payload);
                $message = 'Item ATP ditambahkan.';
            }
        } catch (QueryException) {
            return back()->with('error', 'Urutan ATP bentrok (unik per mapel/kelas/tahun). Ubah nomor urut.');
        }

        return redirect()->route('references.index', [
            'tab' => 'atp',
            'subjectId' => $data['subject_id'],
            'atpGradeFilter' => $data['grade'],
        ])->with('message', $message);
    }

    private function persistSemester(Request $request, ?Semester $semester = null): void
    {
        $data = $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20',
            'number' => 'required|integer|min:1|max:4',
            'starts_on' => 'nullable|date',
            'ends_on' => 'nullable|date|after_or_equal:starts_on',
            'is_active' => 'boolean',
        ]);

        $uniqueNumber = Semester::query()
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('number', $data['number'])
            ->when($semester, fn ($q) => $q->where('id', '!=', $semester->id))
            ->exists();

        if ($uniqueNumber) {
            throw ValidationException::withMessages([
                'number' => 'Nomor semester sudah ada di tahun ajaran ini.',
            ]);
        }

        if ($data['is_active'] ?? false) {
            Semester::query()
                ->where('academic_year_id', $data['academic_year_id'])
                ->update(['is_active' => false]);
        }

        $payload = [
            'academic_year_id' => $data['academic_year_id'],
            'name' => $data['name'],
            'code' => $data['code'],
            'number' => $data['number'],
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ];

        if ($semester) {
            $semester->update($payload);
        } else {
            Semester::query()->create($payload);
        }
    }

    private function persistRombel(Request $request, ?SchoolClass $rombel = null): void
    {
        $data = $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'name' => 'required|string|max:50',
            'rombel_code' => 'nullable|string|max:32',
            'grade' => 'required|integer|min:1|max:12',
            'homeroom_teacher_id' => 'nullable|integer|exists:users,id',
        ]);

        $payload = [
            'academic_year_id' => $data['academic_year_id'],
            'name' => $data['name'],
            'rombel_code' => $data['rombel_code'] ?: $data['name'],
            'grade' => $data['grade'],
            'homeroom_teacher_id' => $data['homeroom_teacher_id'] ?: null,
        ];

        if ($rombel) {
            $rombel->update($payload);
        } else {
            SchoolClass::query()->create($payload);
        }
    }

    private function persistMapel(Request $request, ?Subject $subject = null): void
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:subjects,code,'.($subject !== null ? $subject->id : 'NULL'),
            'phase' => 'required|string|max:5',
            'jenjang' => 'required|string|max:20',
            'description' => 'nullable|string|max:500',
        ]);

        $payload = [
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'phase' => $data['phase'],
            'jenjang' => $data['jenjang'],
            'description' => $data['description'] ?: null,
        ];

        if ($subject) {
            $subject->update($payload);
        } else {
            Subject::query()->create($payload);
        }
    }

    public function canManageSubject(?int $subjectId = null): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->can(PermissionCatalog::REFERENCES_MANAGE) || $user->isAdmin()) {
            return true;
        }

        if (! $subjectId) {
            return false;
        }

        return DB::table('subject_teachers')
            ->where('subject_id', $subjectId)
            ->where('teacher_id', $user->id)
            ->exists()
            || LearningPlan::where('teacher_id', $user->id)->where('subject_id', $subjectId)->exists()
            || Subject::where('id', $subjectId)->where('code', 'INF')->exists();
    }

    private function ensureCanManage(): void
    {
        abort_unless(Auth::user()?->can(PermissionCatalog::REFERENCES_MANAGE), 403);
    }
}
