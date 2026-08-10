<?php

namespace App\Models;

use App\Enums\MaterialStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $plan_id
 * @property array<string, mixed>|null $content
 * @property MaterialStatus $status
 * @property Carbon|null $published_at
 * @property-read LearningPlan|null $plan
 * @property-read Collection<int, LearningEvent> $events
 */
class LearningMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['plan_id', 'content', 'status', 'published_at'];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'status' => MaterialStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<LearningPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(LearningPlan::class, 'plan_id');
    }

    /** @return HasMany<LearningEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(LearningEvent::class, 'material_id');
    }
}
