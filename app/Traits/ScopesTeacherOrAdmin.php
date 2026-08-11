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

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ScopesTeacherOrAdmin
{
    /**
     * Scope query berdasarkan role user:
     * - Admin: seluruh record
     * - Guru: hanya `teacher_id` miliknya
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForCurrentUser(Builder $query, string $teacherColumn = 'teacher_id'): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return $query;
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where($this->getTable().'.'.$teacherColumn, $user->id);
    }
}
