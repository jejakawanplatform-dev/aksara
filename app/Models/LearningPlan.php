<?php

namespace App\Models;

use App\Enums\PlanStatus;
use App\Traits\ScopesTeacherOrAdmin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningPlan extends Model
{
    use HasFactory, SoftDeletes, ScopesTeacherOrAdmin;

    protected $fillable = [
        'teacher_id', 'academic_year_id', 'semester_id', 'class_id', 'subject_id',
        'curriculum_cp_id', 'curriculum_tp_id',
        'phase', 'grade', 'topic', 'duration_minutes', 'learning_objectives',
        'student_needs', 'curriculum_reference', 'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => PlanStatus::class,
            'grade' => 'integer',
        ];
    }

    // ── Relasi ───────────────────────────────────────────────

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function curriculumCp(): BelongsTo
    {
        return $this->belongsTo(CurriculumCp::class);
    }

    public function curriculumTp(): BelongsTo
    {
        return $this->belongsTo(CurriculumTp::class);
    }

    public function material(): HasOne
    {
        return $this->hasOne(LearningMaterial::class, 'plan_id');
    }

    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AiGeneration::class, 'plan_id');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'plan_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'plan_id');
    }

    public function evaluation(): HasOne
    {
        return $this->hasOne(TeacherEvaluation::class, 'plan_id');
    }

    // ── Helper ───────────────────────────────────────────────

    public function isPublished(): bool
    {
        return $this->status === PlanStatus::Published;
    }

    public function isDraft(): bool
    {
        return $this->status === PlanStatus::Draft;
    }
}
