<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function cp(): BelongsTo
    {
        return $this->belongsTo(CurriculumCp::class, 'curriculum_cp_id');
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
        $grade = $this->grade ? " · Kelas {$this->grade}" : '';

        return "{$this->code}{$grade}";
    }
}
