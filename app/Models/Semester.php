<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $academic_year_id
 * @property string $name
 * @property string $code
 * @property int $number
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property bool $is_active
 * @property-read AcademicYear|null $academicYear
 * @property-read Collection<int, CurriculumAtpItem> $atpItems
 * @property-read Collection<int, LearningPlan> $learningPlans
 */
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

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return HasMany<CurriculumAtpItem, $this> */
    public function atpItems(): HasMany
    {
        return $this->hasMany(CurriculumAtpItem::class);
    }

    /** @return HasMany<LearningPlan, $this> */
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
