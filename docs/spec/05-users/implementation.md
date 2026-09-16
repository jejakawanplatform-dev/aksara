# Implementation — Manajemen pengguna

## Artefak

| Area | Path |
|---|---|
| Controller | `app/Http/Controllers/Users/UserController.php`, `app/Http/Controllers/Users/UserExportImportController.php` |
| Service | `app/Services/UserExportImportService.php` |
| Request | `app/Http/Requests/UserImportRequest.php` |
| Page | `resources/js/Pages/Users/Index.vue` |
| Component | `resources/js/Components/users/UserImportModal.vue` |
| UI | Pagination + filter role (semua `UserRole`); IconButton aksi; Export/Import Excel; BulkToolbar actions |
| Model | `app/Models/User.php` (+ relasi classes/children/parents) |
| Routes | `users.*` (`export`, `template`, `import`, `credentials-download`) |
| Test | `tests/Feature/UserManagementTest.php`, `tests/Feature/UserBulkActionTest.php`, `tests/Feature/UserExportImportTest.php` |

## Alur

```text
/users → Users/Index.vue
  POST /users                  buat
  PUT  /users/{user}           update role/data
  DELETE /users/{user}         hapus
  POST /users/bulk-destroy     hapus massal
  GET  /users/export           ekspor excel (.xlsx) semua / sesuai filter / ids terpilih
  GET  /users/template         unduh template resmi (.xlsx)
  POST /users/import           impor excel massal (skip/update, password seragam/acak)
  GET  /users/credentials-download unduh rekap password acak hasil impor
  POST attach-class / child / homeroom
```

## Otorisasi

Middleware `permission:users.manage` (default: admin).
Ekspor/Impor diproteksi ketat hanya untuk akun Administrator (`isAdmin()`).
