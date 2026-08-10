# Implementation — Role & RBAC matrix

## Artefak

| Area | Path |
|---|---|
| Katalog | `app/Support/Access/PermissionCatalog.php` |
| Seeder | `database/seeders/RolePermissionSeeder.php` |
| Controller | `app/Http/Controllers/Access/AccessController.php` |
| Page | `resources/js/Pages/Access/Index.vue` |
| Enum | `app/Enums/UserRole.php` |
| Test | `tests/Feature/RbacMatrixTest.php` |

## Permission inti

`users.manage`, `access.manage`, `settings.manage`, `references.view|manage`, `plans.manage`, `attendance.manage|summary`, `evaluation.manage`, `reports.teacher`, `materials.read`, `quiz.attempt`

## Perilaku

1. GET `/access` — tampilkan matrix per role.
2. PUT `/access` — simpan; kunci permission admin.
3. POST `/access/reset-defaults` — kembalikan default katalog.
4. Nav Inertia memfilter item lewat permission user.
