<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $plan_id
 * @property int $student_id
 * @property AttendanceStatus $status
 * @property string|null $notes
 * @property-read LearningPlan|null $plan
 * @property-read User|null $student
 */
class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = ['plan_id', 'student_id', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
        ];
    }

    /** @return BelongsTo<LearningPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(LearningPlan::class, 'plan_id');
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
