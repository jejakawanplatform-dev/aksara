<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Excused = 'excused';
    case Sick    = 'sick';
    case Absent  = 'absent';

    public function label(): string
    {
        return match($this) {
            self::Present => 'Hadir',
            self::Excused => 'Izin',
            self::Sick    => 'Sakit',
            self::Absent  => 'Alpa',
        };
    }
}
