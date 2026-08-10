<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumAtpItem extends Model
{
    protected $fillable = [
        'subject_id', 'academic_year_id', 'semester_id', 'curriculum_tp_id',
        'grade', 'sequence', 'unit_title', 'estimated_meetings',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'integer',
            'sequence' => 'integer',
            'estimated_meetings' => 'integer',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function tp(): BelongsTo
    {
        return $this->belongsTo(CurriculumTp::class, 'curriculum_tp_id');
    }
}
