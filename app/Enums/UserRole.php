<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin           = 'admin';
    case Teacher         = 'teacher';
    case Student         = 'student';
    case HomeroomTeacher = 'homeroom_teacher';
    case Parent          = 'parent';

    public function label(): string
    {
        return match ($this) {
            self::Admin           => 'Administrator',
            self::Teacher         => 'Guru',
            self::Student         => 'Siswa',
            self::HomeroomTeacher => 'Wali Kelas',
            self::Parent          => 'Wali Murid',
        };
    }

    public function canManageUsers(): bool
    {
        return $this === self::Admin;
    }

    public function canManagePlans(): bool
    {
        return $this === self::Teacher;
    }

    public function canViewClassReport(): bool
    {
        return in_array($this, [self::Teacher, self::HomeroomTeacher], true);
    }

    /** @return list<self> */
    public static function assignable(): array
    {
        return [
            self::Admin,
            self::Teacher,
            self::HomeroomTeacher,
            self::Student,
            self::Parent,
        ];
    }
}
