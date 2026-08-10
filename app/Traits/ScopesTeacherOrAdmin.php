<?php

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
