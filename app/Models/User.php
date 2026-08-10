<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\LearningPlan;
use App\Models\QuizAttempt;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    public function isHomeroomTeacher(): bool
    {
        return $this->role === UserRole::HomeroomTeacher;
    }

    public function isParent(): bool
    {
        return $this->role === UserRole::Parent;
    }

    public function syncAppRole(): void
    {
        $role = $this->role?->value;
        if ($role) {
            $this->syncRoles([$role]);
        }
    }

    public function classIds(): array
    {
        if ($this->isStudent()) {
            return \Illuminate\Support\Facades\DB::table('class_members')
                ->where('student_id', $this->id)
                ->pluck('class_id')
                ->all();
        }

        if ($this->isHomeroomTeacher()) {
            return SchoolClass::where('homeroom_teacher_id', $this->id)->pluck('id')->all();
        }

        return [];
    }

    public function belongsToClass(?int $classId): bool
    {
        if ($classId === null) {
            return false;
        }

        return in_array($classId, $this->classIds(), true);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'student_id');
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'student_id');
    }

    public function learningPlans(): HasMany
    {
        return $this->hasMany(LearningPlan::class, 'teacher_id');
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_members', 'student_id', 'class_id');
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_students', 'parent_id', 'student_id');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_students', 'student_id', 'parent_id');
    }

    public function homeroomClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'homeroom_teacher_id');
    }

    public function taughtSubjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_teachers', 'teacher_id', 'subject_id');
    }
}
