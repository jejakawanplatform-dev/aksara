<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole $role
 * @property-read Collection<int, AttendanceRecord> $attendances
 * @property-read Collection<int, QuizAttempt> $quizAttempts
 * @property-read Collection<int, LearningPlan> $learningPlans
 * @property-read Collection<int, SchoolClass> $classes
 * @property-read Collection<int, User> $children
 * @property-read Collection<int, User> $parents
 * @property-read Collection<int, SchoolClass> $homeroomClasses
 * @property-read Collection<int, Subject> $taughtSubjects
 */
class User extends Authenticatable implements MustVerifyEmail
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

    /**
     * @return list<int>
     */
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

    /** @return HasMany<AttendanceRecord, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'student_id');
    }

    /** @return HasMany<QuizAttempt, $this> */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'student_id');
    }

    /** @return HasMany<LearningPlan, $this> */
    public function learningPlans(): HasMany
    {
        return $this->hasMany(LearningPlan::class, 'teacher_id');
    }

    /** @return BelongsToMany<SchoolClass, $this> */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_members', 'student_id', 'class_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_students', 'parent_id', 'student_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_students', 'student_id', 'parent_id');
    }

    /** @return HasMany<SchoolClass, $this> */
    public function homeroomClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'homeroom_teacher_id');
    }

    /** @return BelongsToMany<Subject, $this> */
    public function taughtSubjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_teachers', 'teacher_id', 'subject_id');
    }
}
