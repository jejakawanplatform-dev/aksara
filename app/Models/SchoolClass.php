<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    use HasFactory;

    protected $table = 'school_classes';

    protected $fillable = [
        'academic_year_id', 'name', 'rombel_code', 'grade', 'homeroom_teacher_id',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_members', 'class_id', 'student_id');
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

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
