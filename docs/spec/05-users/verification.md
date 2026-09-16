# Verification — Manajemen pengguna

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Controller | `app/Http/Controllers/Users/UserController.php` |
| Page | `resources/js/Pages/Users/Index.vue` |
| Model | `app/Models/User.php` |
| Routes | `users.*` (`permission:users.manage`) |
| Tests | `tests/Feature/UserManagementTest.php`, `tests/Feature/UserBulkActionTest.php`, `tests/Feature/UserExportImportTest.php` |

## Checklist

- [x] CRUD + attach class/child/homeroom
- [x] Bulk actions (`users.bulk-destroy`) dengan self-guard & protection relasi aktif
- [x] Export / import spreadsheet pengguna (.xlsx)
- [x] `UserManagementTest`, `UserBulkActionTest`, dan `UserExportImportTest` hijau

## Perintah

```bash
php artisan test --filter=UserManagementTest
php artisan test --filter=UserBulkActionTest
php artisan test --filter=UserExportImportTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| GET `/users` | admin | daftar user |
| Buat siswa + attach kelas | admin | masuk rombel |
