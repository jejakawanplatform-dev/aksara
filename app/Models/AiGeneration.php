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

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $plan_id
 * @property int $created_by
 * @property array<string, mixed>|null $input_summary
 * @property array<string, mixed>|null $output
 * @property string|null $model
 * @property string $review_status
 * @property Carbon|null $reviewed_at
 * @property int|null $reviewed_by
 * @property-read LearningPlan|null $plan
 * @property-read User|null $createdBy
 * @property-read User|null $reviewedBy
 */
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
            'output' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<LearningPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(LearningPlan::class, 'plan_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->review_status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->review_status === 'approved';
    }
}
