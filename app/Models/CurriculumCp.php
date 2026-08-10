<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumCp extends Model
{
    protected $fillable = [
        'subject_id', 'phase', 'element_code', 'element_name',
        'statement', 'source_note', 'sequence',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function tps(): HasMany
    {
        return $this->hasMany(CurriculumTp::class)->orderBy('sequence');
    }

    public function learningPlans(): HasMany
    {
        return $this->hasMany(LearningPlan::class);
    }

    public function label(): string
    {
        return "{$this->element_code} — {$this->element_name}";
    }
}
