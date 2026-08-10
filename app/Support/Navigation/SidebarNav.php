<?php

namespace App\Support\Navigation;

use App\Support\Access\PermissionCatalog;
use Illuminate\Http\Request;

final class SidebarNav
{
    /**
     * @return list<array{title: string, items: list<array{label: string, href: string, active: bool, permission: ?string, icon: string}>}>
     */
    public static function groups(Request $request): array
    {
        $routeIs = static fn (string $pattern): bool => $request->routeIs($pattern);

        return [
            [
                'title' => 'UTAMA',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'href' => route('dashboard'),
                        'active' => $routeIs('dashboard'),
                        'permission' => null,
                        'icon' => 'dashboard',
                    ],
                ],
            ],
            [
                'title' => 'PEMBELAJARAN',
                'items' => [
                    [
                        'label' => '1. Rencana Pembelajaran',
                        'href' => route('plans.index'),
                        'active' => $routeIs('plans.*'),
                        'permission' => PermissionCatalog::PLANS_MANAGE,
                        'icon' => 'plan',
                    ],
                    [
                        'label' => '2. Materi Pembelajaran',
                        'href' => route('materials.index'),
                        'active' => $routeIs('materials.*'),
                        'permission' => PermissionCatalog::PLANS_MANAGE,
                        'icon' => 'material',
                    ],
                    [
                        'label' => '3. Rekap Kehadiran',
                        'href' => route('attendance.summary'),
                        'active' => $routeIs('attendance.summary'),
                        'permission' => PermissionCatalog::ATTENDANCE_SUMMARY,
                        'icon' => 'attendance',
                    ],
                ],
            ],
            [
                'title' => 'SUPERVISI & LAPORAN',
                'items' => [
                    [
                        'label' => '4. Laporan Guru',
                        'href' => route('reports.guru'),
                        'active' => $routeIs('reports.guru'),
                        'permission' => PermissionCatalog::REPORTS_TEACHER,
                        'icon' => 'report',
                    ],
                    [
                        'label' => '5. Evaluasi & Refleksi',
                        'href' => route('evaluations.monitoring'),
                        'active' => $routeIs('evaluations.monitoring'),
                        'permission' => PermissionCatalog::EVALUATION_MANAGE,
                        'icon' => 'evaluation',
                    ],
                ],
            ],
            [
                'title' => 'MASTER DATA',
                'items' => [
                    [
                        'label' => 'Referensi Master',
                        'href' => route('references.index'),
                        'active' => $routeIs('references.*'),
                        'permission' => PermissionCatalog::REFERENCES_VIEW,
                        'icon' => 'references',
                    ],
                ],
            ],
            [
                'title' => 'ADMINISTRASI & SISTEM',
                'items' => [
                    [
                        'label' => 'Kelola Pengguna',
                        'href' => route('users.index'),
                        'active' => $routeIs('users.*'),
                        'permission' => PermissionCatalog::USERS_MANAGE,
                        'icon' => 'users',
                    ],
                    [
                        'label' => 'Matrix Hak Akses',
                        'href' => route('access.index'),
                        'active' => $routeIs('access.*'),
                        'permission' => PermissionCatalog::ACCESS_MANAGE,
                        'icon' => 'access',
                    ],
                    [
                        'label' => 'Pengaturan Sistem',
                        'href' => route('settings.index'),
                        'active' => $routeIs('settings.*'),
                        'permission' => PermissionCatalog::SETTINGS_MANAGE,
                        'icon' => 'settings',
                    ],
                ],
            ],
        ];
    }
}
