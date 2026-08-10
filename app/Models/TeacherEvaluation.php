<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherEvaluation extends Model
{
    use HasFactory;

    protected $fillable = ['plan_id', 'teacher_id', 'notes', 'challenges', 'next_action'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(LearningPlan::class, 'plan_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
