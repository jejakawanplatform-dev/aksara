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
use App\Http\Controllers\Access\AccessController;
use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\Attendance\AttendanceSummaryController;
use App\Http\Controllers\CurriculumExportController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Evaluation\EvaluationController;
use App\Http\Controllers\Evaluation\EvaluationMonitoringController;
use App\Http\Controllers\LearningPlanExportController;
use App\Http\Controllers\Materials\MaterialController;
use App\Http\Controllers\Materials\MaterialEditController;
use App\Http\Controllers\Plans\PlanController;
use App\Http\Controllers\Plans\PlanQuizController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Quiz\QuizAttemptController;
use App\Http\Controllers\References\ReferenceController;
use App\Http\Controllers\References\ReferenceImportController;
use App\Http\Controllers\Reports\TeacherReportController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'permission:plans.manage'])->prefix('plans')->name('plans.')->group(function () {
    Route::get('/', [PlanController::class, 'index'])->name('index');
    Route::get('/create', [PlanController::class, 'create'])->name('create');
    Route::post('/', [PlanController::class, 'store'])->name('store');
    Route::post('/import', [PlanController::class, 'import'])->name('import');
    Route::get('/export/{format}', [LearningPlanExportController::class, 'export'])->name('export');
    Route::get('/import/template', [LearningPlanExportController::class, 'downloadTemplate'])->name('import.template');
    Route::get('/{plan}/export/{format}', [LearningPlanExportController::class, 'exportSingle'])->name('export.single');
    Route::get('/{plan}/edit', [PlanController::class, 'edit'])->name('edit');
    Route::put('/{plan}', [PlanController::class, 'update'])->name('update');
    Route::delete('/{plan}', [PlanController::class, 'destroy'])->name('destroy');
    Route::post('/{plan}/open-material', [PlanController::class, 'openMaterial'])->name('open-material');
    Route::get('/{plan}/draft', [PlanController::class, 'draft'])->name('draft');
    Route::post('/{plan}/draft/approve', [PlanController::class, 'approveDraft'])->name('draft.approve');
    Route::post('/{plan}/draft/publish', [PlanController::class, 'publishDraft'])->name('draft.publish');
    Route::get('/{plan}/quiz', [PlanQuizController::class, 'edit'])->name('quiz');
    Route::post('/{plan}/quiz', [PlanQuizController::class, 'store'])->name('quiz.store');
});

Route::middleware(['auth', 'permission:materials.read|plans.manage'])->prefix('materials')->name('materials.')->group(function () {
    Route::get('/', [MaterialController::class, 'index'])->name('index');
    Route::get('/{material}', [MaterialController::class, 'show'])->name('show');

    /** Inertia + Vue TipTap editor (Tahap 18) */
    Route::get('/{material}/edit', [MaterialEditController::class, 'edit'])->name('edit');
    Route::put('/{material}', [MaterialEditController::class, 'update'])->name('update');
    Route::post('/{material}/publish', [MaterialEditController::class, 'publish'])->name('publish');
    Route::get('/{material}/media', [MaterialEditController::class, 'indexMedia'])->name('media');
    Route::post('/{material}/images', [MaterialEditController::class, 'storeImage'])->name('images');
    Route::delete('/{material}/media/{filename}', [MaterialEditController::class, 'destroyMedia'])
        ->where('filename', '[^/]+')
        ->name('media.destroy');
    Route::post('/{material}/copilot', [MaterialEditController::class, 'copilot'])->name('copilot');
});

Route::middleware(['auth', 'permission:users.manage'])->prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::put('/{user}', [UserController::class, 'update'])->name('update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    Route::post('/{user}/attach-class', [UserController::class, 'attachClass'])->name('attach-class');
    Route::delete('/{user}/classes/{class}', [UserController::class, 'detachClass'])->name('detach-class');
    Route::post('/{user}/attach-child', [UserController::class, 'attachChild'])->name('attach-child');
    Route::delete('/{user}/children/{child}', [UserController::class, 'detachChild'])->name('detach-child');
    Route::post('/{user}/homeroom', [UserController::class, 'saveHomeroom'])->name('homeroom');
});

Route::middleware(['auth', 'permission:access.manage'])->prefix('access')->name('access.')->group(function () {
    Route::get('/', [AccessController::class, 'index'])->name('index');
    Route::put('/', [AccessController::class, 'save'])->name('save');
    Route::post('/reset-defaults', [AccessController::class, 'resetDefaults'])->name('reset-defaults');
});

Route::middleware(['auth', 'permission:settings.manage'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::put('/', [SettingsController::class, 'save'])->name('save');
    Route::post('/providers', [SettingsController::class, 'storeProvider'])->name('providers.store');
    Route::post('/providers/test', [SettingsController::class, 'testConnection'])->name('providers.test');
    Route::put('/providers/{provider}', [SettingsController::class, 'updateProvider'])->name('providers.update');
    Route::delete('/providers/{provider}', [SettingsController::class, 'destroyProvider'])->name('providers.destroy');
    Route::post('/providers/{provider}/toggle', [SettingsController::class, 'toggleProvider'])->name('providers.toggle');
    Route::post('/providers/{provider}/priority', [SettingsController::class, 'movePriority'])->name('providers.priority');
});

Route::middleware(['auth', 'permission:references.view'])->prefix('references')->name('references.')->group(function () {
    Route::get('/', [ReferenceController::class, 'index'])->name('index');
    Route::get('/profil-sekolah', [ReferenceController::class, 'index'])->name('section.school');
    Route::get('/data-akademik', [ReferenceController::class, 'index'])->name('section.academic');
    Route::get('/kurikulum', [ReferenceController::class, 'index'])->name('section.curriculum');
    Route::get('/export/cp-tp/{subject}/{format}', [CurriculumExportController::class, 'exportCpTp'])->name('export.cp-tp');
    Route::get('/export/atp/{subject}/{format}', [CurriculumExportController::class, 'exportAtp'])->name('export.atp');

    Route::post('/school', [ReferenceController::class, 'saveSchoolProfile'])->name('school');
    Route::post('/academic', [ReferenceController::class, 'saveAcademicOps'])->name('academic');

    Route::post('/years', [ReferenceController::class, 'storeYear'])->name('years.store');
    Route::put('/years/{year}', [ReferenceController::class, 'updateYear'])->name('years.update');
    Route::delete('/years/{year}', [ReferenceController::class, 'destroyYear'])->name('years.destroy');

    Route::post('/semesters', [ReferenceController::class, 'storeSemester'])->name('semesters.store');
    Route::put('/semesters/{semester}', [ReferenceController::class, 'updateSemester'])->name('semesters.update');
    Route::delete('/semesters/{semester}', [ReferenceController::class, 'destroySemester'])->name('semesters.destroy');
    Route::post('/semesters/{semester}/activate', [ReferenceController::class, 'activateSemester'])->name('semesters.activate');

    Route::post('/rombels', [ReferenceController::class, 'storeRombel'])->name('rombels.store');
    Route::put('/rombels/{rombel}', [ReferenceController::class, 'updateRombel'])->name('rombels.update');
    Route::delete('/rombels/{rombel}', [ReferenceController::class, 'destroyRombel'])->name('rombels.destroy');
    Route::post('/rombels/{rombel}/attach-student', [ReferenceController::class, 'attachStudent'])->name('rombels.attach-student');
    Route::delete('/rombels/{rombel}/students/{student}', [ReferenceController::class, 'detachStudent'])->name('rombels.detach-student');
    Route::post('/rombels/{rombel}/enrol', [ReferenceController::class, 'toggleTeacherEnrolment'])->name('rombels.enrol');

    Route::post('/mapel', [ReferenceController::class, 'storeMapel'])->name('mapel.store');
    Route::put('/mapel/{subject}', [ReferenceController::class, 'updateMapel'])->name('mapel.update');
    Route::delete('/mapel/{subject}', [ReferenceController::class, 'destroyMapel'])->name('mapel.destroy');
    Route::post('/mapel/{subject}/teachers', [ReferenceController::class, 'saveSubjectTeachers'])->name('mapel.teachers');

    Route::post('/cps', [ReferenceController::class, 'storeCp'])->name('cps.store');
    Route::put('/cps/{cp}', [ReferenceController::class, 'updateCp'])->name('cps.update');
    Route::delete('/cps/{cp}', [ReferenceController::class, 'destroyCp'])->name('cps.destroy');

    Route::post('/tps', [ReferenceController::class, 'storeTp'])->name('tps.store');
    Route::put('/tps/{tp}', [ReferenceController::class, 'updateTp'])->name('tps.update');
    Route::delete('/tps/{tp}', [ReferenceController::class, 'destroyTp'])->name('tps.destroy');

    Route::post('/atp', [ReferenceController::class, 'storeAtp'])->name('atp.store');
    Route::put('/atp/{atp}', [ReferenceController::class, 'updateAtp'])->name('atp.update');
    Route::delete('/atp/{atp}', [ReferenceController::class, 'destroyAtp'])->name('atp.destroy');

    Route::post('/import/cp-tp', [ReferenceImportController::class, 'importCpTp'])->name('import.cp-tp');
    Route::post('/import/atp', [ReferenceImportController::class, 'importAtp'])->name('import.atp');
});

Route::middleware(['auth', 'permission:attendance.manage'])->group(function () {
    Route::get('/plans/{plan}/attendance', [AttendanceController::class, 'edit'])->name('attendance.form');
    Route::post('/plans/{plan}/attendance', [AttendanceController::class, 'save'])->name('attendance.save');
});

Route::middleware(['auth', 'permission:evaluation.manage'])->group(function () {
    Route::get('/plans/{plan}/evaluation', [EvaluationController::class, 'edit'])->name('evaluation.form');
    Route::post('/plans/{plan}/evaluation', [EvaluationController::class, 'save'])->name('evaluation.save');
    Route::get('/evaluations/monitoring', [EvaluationMonitoringController::class, 'index'])->name('evaluations.monitoring');
});

Route::middleware(['auth', 'permission:reports.teacher'])->group(function () {
    Route::get('/reports/guru', [TeacherReportController::class, 'index'])->name('reports.guru');
});

Route::middleware(['auth', 'permission:attendance.summary'])->group(function () {
    Route::get('/attendance/summary', [AttendanceSummaryController::class, 'index'])->name('attendance.summary');
});

Route::middleware(['auth', 'permission:quiz.attempt'])->group(function () {
    Route::get('/quiz/{quiz}', [QuizAttemptController::class, 'show'])->name('quiz.attempt');
    Route::post('/quiz/{quiz}', [QuizAttemptController::class, 'submit'])->name('quiz.attempt.submit');
});
require __DIR__.'/auth.php';
