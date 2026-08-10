<?php

namespace App\Models;

use App\Enums\PlanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['plan_id', 'title', 'questions', 'status'];

    protected function casts(): array
    {
        return [
            'questions' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(LearningPlan::class, 'plan_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
