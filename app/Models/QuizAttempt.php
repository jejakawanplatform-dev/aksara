<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $quiz_id
 * @property int $student_id
 * @property array<string, mixed>|null $answers
 * @property int $score
 * @property Carbon|null $submitted_at
 * @property-read Quiz|null $quiz
 * @property-read User|null $student
 */
class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = ['quiz_id', 'student_id', 'answers', 'score', 'submitted_at'];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Quiz, $this> */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function passed(int $passingScore = 70): bool
    {
        return $this->score >= $passingScore;
    }
}
