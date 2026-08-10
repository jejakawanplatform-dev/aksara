<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property bool $is_active
 * @property-read Collection<int, SchoolClass> $classes
 * @property-read Collection<int, Semester> $semesters
 * @property-read Collection<int, CurriculumAtpItem> $atpItems
 * @property-read Collection<int, LearningPlan> $learningPlans
 */
class AcademicYear extends Model
{
    protected $fillable = [
        'name', 'code', 'starts_on', 'ends_on', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<SchoolClass, $this> */
    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    /** @return HasMany<Semester, $this> */
    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class)->orderBy('number');
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

    public static function active(): ?self
    {
        return static::query()->where('is_active', true)->first();
    }
}
