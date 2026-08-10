<?php

namespace App\Support\Access;

use App\Enums\UserRole;

/**
 * Katalog permission tetap + matrix default per role (enum).
 * Role tidak dibuat lewat UI — hanya assignment permission ke role tetap.
 */
final class PermissionCatalog
{
    public const USERS_MANAGE = 'users.manage';

    public const ACCESS_MANAGE = 'access.manage';

    public const SETTINGS_MANAGE = 'settings.manage';

    public const REFERENCES_VIEW = 'references.view';

    public const REFERENCES_MANAGE = 'references.manage';

    public const PLANS_MANAGE = 'plans.manage';

    public const ATTENDANCE_MANAGE = 'attendance.manage';

    public const ATTENDANCE_SUMMARY = 'attendance.summary';

    public const EVALUATION_MANAGE = 'evaluation.manage';

    public const REPORTS_TEACHER = 'reports.teacher';

    public const MATERIALS_READ = 'materials.read';

    public const QUIZ_ATTEMPT = 'quiz.attempt';

    /**
     * @return array<string, string> name => label
     */
    public static function definitions(): array
    {
        return [
            self::USERS_MANAGE => 'Kelola pengguna',
            self::ACCESS_MANAGE => 'Kelola matrix hak akses',
            self::SETTINGS_MANAGE => 'Kelola pengaturan sistem',
            self::REFERENCES_VIEW => 'Lihat referensi kurikulum',
            self::REFERENCES_MANAGE => 'CRUD referensi kurikulum',
            self::PLANS_MANAGE => 'Kelola rencana & materi/kuis',
            self::ATTENDANCE_MANAGE => 'Input kehadiran',
            self::ATTENDANCE_SUMMARY => 'Rekap kehadiran',
            self::EVALUATION_MANAGE => 'Evaluasi guru',
            self::REPORTS_TEACHER => 'Laporan guru',
            self::MATERIALS_READ => 'Baca materi published',
            self::QUIZ_ATTEMPT => 'Kerjakan kuis',
        ];
    }

    /**
     * Permission yang tidak boleh dicabut dari role admin.
     *
     * @return list<string>
     */
    public static function lockedForAdmin(): array
    {
        return [
            self::USERS_MANAGE,
            self::ACCESS_MANAGE,
            self::SETTINGS_MANAGE,
        ];
    }

    /**
     * Matrix default: role value => permission names.
     *
     * @return array<string, list<string>>
     */
    public static function defaultMatrix(): array
    {
        return [
            UserRole::Admin->value => [
                self::USERS_MANAGE,
                self::ACCESS_MANAGE,
                self::SETTINGS_MANAGE,
                self::REFERENCES_VIEW,
                self::REFERENCES_MANAGE,
                self::PLANS_MANAGE,
                self::EVALUATION_MANAGE,
                self::REPORTS_TEACHER,
                self::MATERIALS_READ,
            ],
            UserRole::Teacher->value => [
                self::REFERENCES_VIEW,
                self::PLANS_MANAGE,
                self::ATTENDANCE_MANAGE,
                self::ATTENDANCE_SUMMARY,
                self::EVALUATION_MANAGE,
                self::REPORTS_TEACHER,
                self::MATERIALS_READ,
            ],
            UserRole::HomeroomTeacher->value => [
                self::ATTENDANCE_SUMMARY,
            ],
            UserRole::Student->value => [
                self::MATERIALS_READ,
                self::QUIZ_ATTEMPT,
            ],
            UserRole::Parent->value => [],
        ];
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * Form-safe matrix key (dots → underscores) for nested Vue/Inertia payloads.
     * Method name kept as wireKey for BC with existing Access UI + tests.
     */
    public static function wireKey(string $permission): string
    {
        return str_replace('.', '__', $permission);
    }

    public static function fromWireKey(string $wireKey): string
    {
        return str_replace('__', '.', $wireKey);
    }
}
