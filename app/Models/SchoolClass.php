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

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $academic_year_id
 * @property string $name
 * @property string|null $rombel_code
 * @property int $grade
 * @property int|null $homeroom_teacher_id
 * @property-read AcademicYear|null $academicYear
 * @property-read User|null $homeroomTeacher
 * @property-read Collection<int, User> $students
 * @property-read Collection<int, LearningPlan> $learningPlans
 */
class SchoolClass extends Model
{
    use HasFactory;

    protected $table = 'school_classes';

    protected $fillable = [
        'academic_year_id', 'name', 'rombel_code', 'grade', 'homeroom_teacher_id',
    ];

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_members', 'class_id', 'student_id');
    }

    /** @return BelongsTo<User, $this> */
    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    /** @return HasMany<LearningPlan, $this> */
    public function learningPlans(): HasMany
    {
        return $this->hasMany(LearningPlan::class, 'class_id');
    }

    public function label(): string
    {
        if ($this->rombel_code && $this->rombel_code !== $this->name) {
            return "{$this->name} · {$this->rombel_code}";
        }

        return $this->name ?: $this->rombel_code ?: 'Kelas';
    }

    public function displayName(): string
    {
        $rombel = $this->label();
        $year = $this->academicYear?->name;

        return $year ? "{$rombel} ({$year})" : $rombel;
    }
}
