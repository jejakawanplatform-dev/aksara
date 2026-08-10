<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $plan_id
 * @property int $teacher_id
 * @property string $notes
 * @property string $challenges
 * @property string $next_action
 * @property-read LearningPlan|null $plan
 * @property-read User|null $teacher
 */
class TeacherEvaluation extends Model
{
    use HasFactory;

    protected $fillable = ['plan_id', 'teacher_id', 'notes', 'challenges', 'next_action'];

    /** @return BelongsTo<LearningPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(LearningPlan::class, 'plan_id');
    }

    /** @return BelongsTo<User, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
