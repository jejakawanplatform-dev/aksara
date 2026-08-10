<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ScopesTeacherOrAdmin
{
    /**
     * Scope query berdasarkan role user:
     * - Admin / User dengan permission khusus: Dapat mengakses seluruh record (Global).
     * - Guru: Ter-scope hanya pada record miliknya (teacher_id = auth->id).
     */
    public function scopeForCurrentUser(Builder $query, string $teacherColumn = 'teacher_id'): Builder
    {
        $user = auth()->user();

        if (!$user) {
            return $query;
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where($this->getTable() . '.' . $teacherColumn, $user->id);
    }
}
