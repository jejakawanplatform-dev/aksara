<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $plan_id
 * @property string $title
 * @property array<int, array<string, mixed>>|null $questions
 * @property string $status
 * @property-read LearningPlan|null $plan
 * @property-read Collection<int, QuizAttempt> $attempts
 */
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

    /** @return BelongsTo<LearningPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(LearningPlan::class, 'plan_id');
    }

    /** @return HasMany<QuizAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
