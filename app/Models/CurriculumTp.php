<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $curriculum_cp_id
 * @property string $code
 * @property string $statement
 * @property int|null $grade
 * @property int $sequence
 * @property-read CurriculumCp|null $cp
 * @property-read Collection<int, CurriculumAtpItem> $atpItems
 * @property-read Collection<int, LearningPlan> $learningPlans
 */
class CurriculumTp extends Model
{
    protected $fillable = [
        'curriculum_cp_id', 'code', 'statement', 'grade', 'sequence',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'integer',
            'sequence' => 'integer',
        ];
    }

    /** @return BelongsTo<CurriculumCp, $this> */
    public function cp(): BelongsTo
    {
        return $this->belongsTo(CurriculumCp::class, 'curriculum_cp_id');
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
        $grade = $this->grade ? " · Kelas {$this->grade}" : '';

        return "{$this->code}{$grade}";
    }
}
