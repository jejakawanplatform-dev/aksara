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

namespace App\Models;

use App\Enums\PlanStatus;
use App\Traits\ScopesTeacherOrAdmin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $teacher_id
 * @property int|null $academic_year_id
 * @property int|null $semester_id
 * @property int|null $class_id
 * @property int|null $subject_id
 * @property int|null $curriculum_cp_id
 * @property int|null $curriculum_tp_id
 * @property string $phase
 * @property int $grade
 * @property string $topic
 * @property int $duration_minutes
 * @property string $learning_objectives
 * @property string|null $student_needs
 * @property string $curriculum_reference
 * @property PlanStatus $status
 * @property-read User|null $teacher
 * @property-read AcademicYear|null $academicYear
 * @property-read Semester|null $semester
 * @property-read SchoolClass|null $class
 * @property-read Subject|null $subject
 * @property-read CurriculumCp|null $curriculumCp
 * @property-read CurriculumTp|null $curriculumTp
 * @property-read LearningMaterial|null $material
 * @property-read Collection<int, AiGeneration> $aiGenerations
 * @property-read Collection<int, AttendanceRecord> $attendance
 * @property-read Collection<int, Quiz> $quizzes
 * @property-read TeacherEvaluation|null $evaluation
 */
class LearningPlan extends Model
{
    use HasFactory, ScopesTeacherOrAdmin, SoftDeletes;

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

    /** @return BelongsTo<User, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return BelongsTo<Semester, $this> */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /** @return BelongsTo<SchoolClass, $this> */
    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<CurriculumCp, $this> */
    public function curriculumCp(): BelongsTo
    {
        return $this->belongsTo(CurriculumCp::class);
    }

    /** @return BelongsTo<CurriculumTp, $this> */
    public function curriculumTp(): BelongsTo
    {
        return $this->belongsTo(CurriculumTp::class);
    }

    /** @return HasOne<LearningMaterial, $this> */
    public function material(): HasOne
    {
        return $this->hasOne(LearningMaterial::class, 'plan_id');
    }

    /** @return HasMany<AiGeneration, $this> */
    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AiGeneration::class, 'plan_id');
    }

    /** @return HasMany<AttendanceRecord, $this> */
    public function attendance(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'plan_id');
    }

    /** @return HasMany<Quiz, $this> */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'plan_id');
    }

    /** @return HasOne<TeacherEvaluation, $this> */
    public function evaluation(): HasOne
    {
        return $this->hasOne(TeacherEvaluation::class, 'plan_id');
    }

    public function isPublished(): bool
    {
        return $this->status === PlanStatus::Published;
    }

    public function isDraft(): bool
    {
        return $this->status === PlanStatus::Draft;
    }
}
