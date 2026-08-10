<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = ['quiz_id', 'student_id', 'answers', 'score', 'submitted_at'];

    protected function casts(): array
    {
        return [
            'answers'      => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function passed(int $passingScore = 70): bool
    {
        return $this->score >= $passingScore;
    }
}
