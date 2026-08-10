<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/dashboard', \App\Http\Controllers\Dashboard\DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'permission:plans.manage'])->prefix('plans')->name('plans.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Plans\PlanController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Plans\PlanController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Plans\PlanController::class, 'store'])->name('store');
    Route::post('/import', [\App\Http\Controllers\Plans\PlanController::class, 'import'])->name('import');
    Route::get('/export/{format}', [\App\Http\Controllers\LearningPlanExportController::class, 'export'])->name('export');
    Route::get('/import/template', [\App\Http\Controllers\LearningPlanExportController::class, 'downloadTemplate'])->name('import.template');
    Route::get('/{plan}/export/{format}', [\App\Http\Controllers\LearningPlanExportController::class, 'exportSingle'])->name('export.single');
    Route::get('/{plan}/edit', [\App\Http\Controllers\Plans\PlanController::class, 'edit'])->name('edit');
    Route::put('/{plan}', [\App\Http\Controllers\Plans\PlanController::class, 'update'])->name('update');
    Route::delete('/{plan}', [\App\Http\Controllers\Plans\PlanController::class, 'destroy'])->name('destroy');
    Route::post('/{plan}/open-material', [\App\Http\Controllers\Plans\PlanController::class, 'openMaterial'])->name('open-material');
    Route::get('/{plan}/draft', [\App\Http\Controllers\Plans\PlanController::class, 'draft'])->name('draft');
    Route::post('/{plan}/draft/approve', [\App\Http\Controllers\Plans\PlanController::class, 'approveDraft'])->name('draft.approve');
    Route::post('/{plan}/draft/publish', [\App\Http\Controllers\Plans\PlanController::class, 'publishDraft'])->name('draft.publish');
    Route::get('/{plan}/quiz', [\App\Http\Controllers\Plans\PlanQuizController::class, 'edit'])->name('quiz');
    Route::post('/{plan}/quiz', [\App\Http\Controllers\Plans\PlanQuizController::class, 'store'])->name('quiz.store');
});

Route::middleware(['auth', 'permission:materials.read|plans.manage'])->prefix('materials')->name('materials.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Materials\MaterialController::class, 'index'])->name('index');
    Route::get('/{material}', [\App\Http\Controllers\Materials\MaterialController::class, 'show'])->name('show');

    /** Inertia + Vue TipTap editor (Tahap 18) */
    Route::get('/{material}/edit', [\App\Http\Controllers\Materials\MaterialEditController::class, 'edit'])->name('edit');
    Route::put('/{material}', [\App\Http\Controllers\Materials\MaterialEditController::class, 'update'])->name('update');
    Route::post('/{material}/publish', [\App\Http\Controllers\Materials\MaterialEditController::class, 'publish'])->name('publish');
    Route::get('/{material}/media', [\App\Http\Controllers\Materials\MaterialEditController::class, 'indexMedia'])->name('media');
    Route::post('/{material}/images', [\App\Http\Controllers\Materials\MaterialEditController::class, 'storeImage'])->name('images');
    Route::delete('/{material}/media/{filename}', [\App\Http\Controllers\Materials\MaterialEditController::class, 'destroyMedia'])
        ->where('filename', '[^/]+')
        ->name('media.destroy');
    Route::post('/{material}/copilot', [\App\Http\Controllers\Materials\MaterialEditController::class, 'copilot'])->name('copilot');
});

Route::middleware(['auth', 'permission:users.manage'])->prefix('users')->name('users.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Users\UserController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Users\UserController::class, 'store'])->name('store');
    Route::put('/{user}', [\App\Http\Controllers\Users\UserController::class, 'update'])->name('update');
    Route::delete('/{user}', [\App\Http\Controllers\Users\UserController::class, 'destroy'])->name('destroy');
    Route::post('/{user}/attach-class', [\App\Http\Controllers\Users\UserController::class, 'attachClass'])->name('attach-class');
    Route::delete('/{user}/classes/{class}', [\App\Http\Controllers\Users\UserController::class, 'detachClass'])->name('detach-class');
    Route::post('/{user}/attach-child', [\App\Http\Controllers\Users\UserController::class, 'attachChild'])->name('attach-child');
    Route::delete('/{user}/children/{child}', [\App\Http\Controllers\Users\UserController::class, 'detachChild'])->name('detach-child');
    Route::post('/{user}/homeroom', [\App\Http\Controllers\Users\UserController::class, 'saveHomeroom'])->name('homeroom');
});

Route::middleware(['auth', 'permission:access.manage'])->prefix('access')->name('access.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Access\AccessController::class, 'index'])->name('index');
    Route::put('/', [\App\Http\Controllers\Access\AccessController::class, 'save'])->name('save');
    Route::post('/reset-defaults', [\App\Http\Controllers\Access\AccessController::class, 'resetDefaults'])->name('reset-defaults');
});

Route::middleware(['auth', 'permission:settings.manage'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Settings\SettingsController::class, 'index'])->name('index');
    Route::put('/', [\App\Http\Controllers\Settings\SettingsController::class, 'save'])->name('save');
    Route::post('/providers', [\App\Http\Controllers\Settings\SettingsController::class, 'storeProvider'])->name('providers.store');
    Route::post('/providers/test', [\App\Http\Controllers\Settings\SettingsController::class, 'testConnection'])->name('providers.test');
    Route::put('/providers/{provider}', [\App\Http\Controllers\Settings\SettingsController::class, 'updateProvider'])->name('providers.update');
    Route::delete('/providers/{provider}', [\App\Http\Controllers\Settings\SettingsController::class, 'destroyProvider'])->name('providers.destroy');
    Route::post('/providers/{provider}/toggle', [\App\Http\Controllers\Settings\SettingsController::class, 'toggleProvider'])->name('providers.toggle');
    Route::post('/providers/{provider}/priority', [\App\Http\Controllers\Settings\SettingsController::class, 'movePriority'])->name('providers.priority');
});

Route::middleware(['auth', 'permission:references.view'])->prefix('references')->name('references.')->group(function () {
    Route::get('/', [\App\Http\Controllers\References\ReferenceController::class, 'index'])->name('index');
    Route::get('/export/cp-tp/{subject}/{format}', [\App\Http\Controllers\CurriculumExportController::class, 'exportCpTp'])->name('export.cp-tp');
    Route::get('/export/atp/{subject}/{format}', [\App\Http\Controllers\CurriculumExportController::class, 'exportAtp'])->name('export.atp');

    Route::post('/school', [\App\Http\Controllers\References\ReferenceController::class, 'saveSchoolProfile'])->name('school');
    Route::post('/academic', [\App\Http\Controllers\References\ReferenceController::class, 'saveAcademicOps'])->name('academic');

    Route::post('/years', [\App\Http\Controllers\References\ReferenceController::class, 'storeYear'])->name('years.store');
    Route::put('/years/{year}', [\App\Http\Controllers\References\ReferenceController::class, 'updateYear'])->name('years.update');
    Route::delete('/years/{year}', [\App\Http\Controllers\References\ReferenceController::class, 'destroyYear'])->name('years.destroy');

    Route::post('/semesters', [\App\Http\Controllers\References\ReferenceController::class, 'storeSemester'])->name('semesters.store');
    Route::put('/semesters/{semester}', [\App\Http\Controllers\References\ReferenceController::class, 'updateSemester'])->name('semesters.update');
    Route::delete('/semesters/{semester}', [\App\Http\Controllers\References\ReferenceController::class, 'destroySemester'])->name('semesters.destroy');
    Route::post('/semesters/{semester}/activate', [\App\Http\Controllers\References\ReferenceController::class, 'activateSemester'])->name('semesters.activate');

    Route::post('/rombels', [\App\Http\Controllers\References\ReferenceController::class, 'storeRombel'])->name('rombels.store');
    Route::put('/rombels/{rombel}', [\App\Http\Controllers\References\ReferenceController::class, 'updateRombel'])->name('rombels.update');
    Route::delete('/rombels/{rombel}', [\App\Http\Controllers\References\ReferenceController::class, 'destroyRombel'])->name('rombels.destroy');
    Route::post('/rombels/{rombel}/attach-student', [\App\Http\Controllers\References\ReferenceController::class, 'attachStudent'])->name('rombels.attach-student');
    Route::delete('/rombels/{rombel}/students/{student}', [\App\Http\Controllers\References\ReferenceController::class, 'detachStudent'])->name('rombels.detach-student');
    Route::post('/rombels/{rombel}/enrol', [\App\Http\Controllers\References\ReferenceController::class, 'toggleTeacherEnrolment'])->name('rombels.enrol');

    Route::post('/mapel', [\App\Http\Controllers\References\ReferenceController::class, 'storeMapel'])->name('mapel.store');
    Route::put('/mapel/{subject}', [\App\Http\Controllers\References\ReferenceController::class, 'updateMapel'])->name('mapel.update');
    Route::delete('/mapel/{subject}', [\App\Http\Controllers\References\ReferenceController::class, 'destroyMapel'])->name('mapel.destroy');
    Route::post('/mapel/{subject}/teachers', [\App\Http\Controllers\References\ReferenceController::class, 'saveSubjectTeachers'])->name('mapel.teachers');

    Route::post('/cps', [\App\Http\Controllers\References\ReferenceController::class, 'storeCp'])->name('cps.store');
    Route::put('/cps/{cp}', [\App\Http\Controllers\References\ReferenceController::class, 'updateCp'])->name('cps.update');
    Route::delete('/cps/{cp}', [\App\Http\Controllers\References\ReferenceController::class, 'destroyCp'])->name('cps.destroy');

    Route::post('/tps', [\App\Http\Controllers\References\ReferenceController::class, 'storeTp'])->name('tps.store');
    Route::put('/tps/{tp}', [\App\Http\Controllers\References\ReferenceController::class, 'updateTp'])->name('tps.update');
    Route::delete('/tps/{tp}', [\App\Http\Controllers\References\ReferenceController::class, 'destroyTp'])->name('tps.destroy');

    Route::post('/atp', [\App\Http\Controllers\References\ReferenceController::class, 'storeAtp'])->name('atp.store');
    Route::put('/atp/{atp}', [\App\Http\Controllers\References\ReferenceController::class, 'updateAtp'])->name('atp.update');
    Route::delete('/atp/{atp}', [\App\Http\Controllers\References\ReferenceController::class, 'destroyAtp'])->name('atp.destroy');

    Route::post('/import/cp-tp', [\App\Http\Controllers\References\ReferenceImportController::class, 'importCpTp'])->name('import.cp-tp');
    Route::post('/import/atp', [\App\Http\Controllers\References\ReferenceImportController::class, 'importAtp'])->name('import.atp');
});

Route::middleware(['auth', 'permission:attendance.manage'])->group(function () {
    Route::get('/plans/{plan}/attendance', [\App\Http\Controllers\Attendance\AttendanceController::class, 'edit'])->name('attendance.form');
    Route::post('/plans/{plan}/attendance', [\App\Http\Controllers\Attendance\AttendanceController::class, 'save'])->name('attendance.save');
});

Route::middleware(['auth', 'permission:evaluation.manage'])->group(function () {
    Route::get('/plans/{plan}/evaluation', [\App\Http\Controllers\Evaluation\EvaluationController::class, 'edit'])->name('evaluation.form');
    Route::post('/plans/{plan}/evaluation', [\App\Http\Controllers\Evaluation\EvaluationController::class, 'save'])->name('evaluation.save');
    Route::get('/evaluations/monitoring', [\App\Http\Controllers\Evaluation\EvaluationMonitoringController::class, 'index'])->name('evaluations.monitoring');
});

Route::middleware(['auth', 'permission:reports.teacher'])->group(function () {
    Route::get('/reports/guru', [\App\Http\Controllers\Reports\TeacherReportController::class, 'index'])->name('reports.guru');
});

Route::middleware(['auth', 'permission:attendance.summary'])->group(function () {
    Route::get('/attendance/summary', [\App\Http\Controllers\Attendance\AttendanceSummaryController::class, 'index'])->name('attendance.summary');
});

Route::middleware(['auth', 'permission:quiz.attempt'])->group(function () {
    Route::get('/quiz/{quiz}', [\App\Http\Controllers\Quiz\QuizAttemptController::class, 'show'])->name('quiz.attempt');
    Route::post('/quiz/{quiz}', [\App\Http\Controllers\Quiz\QuizAttemptController::class, 'submit'])->name('quiz.attempt.submit');
});
require __DIR__.'/auth.php';
