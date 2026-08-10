<?php

namespace App\Models;

use App\Enums\MaterialStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['plan_id', 'content', 'status', 'published_at'];

    protected function casts(): array
    {
        return [
            'content'      => 'array',
            'status'       => MaterialStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(LearningPlan::class, 'plan_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(LearningEvent::class, 'material_id');
    }
}
