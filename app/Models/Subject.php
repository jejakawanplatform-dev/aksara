<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'phase', 'jenjang', 'description',
    ];

    public function learningPlans(): HasMany
    {
        return $this->hasMany(LearningPlan::class);
    }

    public function cps(): HasMany
    {
        return $this->hasMany(CurriculumCp::class)->orderBy('sequence');
    }

    public function atpItems(): HasMany
    {
        return $this->hasMany(CurriculumAtpItem::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subject_teachers', 'subject_id', 'teacher_id');
    }

    public function label(): string
    {
        $bits = [$this->name];
        if ($this->phase) {
            $bits[] = "Fase {$this->phase}";
        }
        if ($this->jenjang) {
            $bits[] = $this->jenjang;
        }

        return implode(' · ', $bits);
    }
}
