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

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $phase
 * @property string|null $jenjang
 * @property string|null $description
 * @property-read Collection<int, LearningPlan> $learningPlans
 * @property-read Collection<int, CurriculumCp> $cps
 * @property-read Collection<int, CurriculumAtpItem> $atpItems
 * @property-read Collection<int, User> $teachers
 */
class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'phase', 'jenjang', 'description',
    ];

    /** @return HasMany<LearningPlan, $this> */
    public function learningPlans(): HasMany
    {
        return $this->hasMany(LearningPlan::class);
    }

    /** @return HasMany<CurriculumCp, $this> */
    public function cps(): HasMany
    {
        return $this->hasMany(CurriculumCp::class)->orderBy('sequence');
    }

    /** @return HasMany<CurriculumAtpItem, $this> */
    public function atpItems(): HasMany
    {
        return $this->hasMany(CurriculumAtpItem::class);
    }

    /** @return BelongsToMany<User, $this> */
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
