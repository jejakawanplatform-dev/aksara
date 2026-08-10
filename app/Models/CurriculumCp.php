<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $subject_id
 * @property string $phase
 * @property string $element_code
 * @property string $element_name
 * @property string $statement
 * @property string|null $source_note
 * @property int $sequence
 * @property-read Subject|null $subject
 * @property-read Collection<int, CurriculumTp> $tps
 * @property-read Collection<int, LearningPlan> $learningPlans
 */
class CurriculumCp extends Model
{
    protected $fillable = [
        'subject_id', 'phase', 'element_code', 'element_name',
        'statement', 'source_note', 'sequence',
    ];

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return HasMany<CurriculumTp, $this> */
    public function tps(): HasMany
    {
        return $this->hasMany(CurriculumTp::class)->orderBy('sequence');
    }

    /** @return HasMany<LearningPlan, $this> */
    public function learningPlans(): HasMany
    {
        return $this->hasMany(LearningPlan::class);
    }

    public function label(): string
    {
        return "{$this->element_code} — {$this->element_name}";
    }
}
