<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    protected $fillable = [
        'academic_year_id', 'name', 'code', 'number',
        'starts_on', 'ends_on', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function atpItems(): HasMany
    {
        return $this->hasMany(CurriculumAtpItem::class);
    }

    public function learningPlans(): HasMany
    {
        return $this->hasMany(LearningPlan::class);
    }

    public function label(): string
    {
        $year = $this->academicYear?->name;

        return $year ? "{$this->name} {$year}" : $this->name;
    }

    public static function active(): ?self
    {
        return static::query()->where('is_active', true)->first();
    }
}
