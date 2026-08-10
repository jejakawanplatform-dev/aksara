<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id', 'created_by', 'input_summary', 'output',
        'model', 'review_status', 'reviewed_at', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'input_summary' => 'array',
            'output'        => 'array',
            'reviewed_at'   => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(LearningPlan::class, 'plan_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool  { return $this->review_status === 'pending'; }
    public function isApproved(): bool { return $this->review_status === 'approved'; }
}
