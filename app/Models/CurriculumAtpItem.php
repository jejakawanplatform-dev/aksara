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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $subject_id
 * @property int|null $academic_year_id
 * @property int|null $semester_id
 * @property int|null $curriculum_tp_id
 * @property int $grade
 * @property int $sequence
 * @property string|null $unit_title
 * @property int|null $estimated_meetings
 * @property-read Subject|null $subject
 * @property-read AcademicYear|null $academicYear
 * @property-read Semester|null $semester
 * @property-read CurriculumTp|null $tp
 */
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

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return BelongsTo<Semester, $this> */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /** @return BelongsTo<CurriculumTp, $this> */
    public function tp(): BelongsTo
    {
        return $this->belongsTo(CurriculumTp::class, 'curriculum_tp_id');
    }
}
