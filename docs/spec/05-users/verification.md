# Verification — Manajemen pengguna

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Controller | `app/Http/Controllers/Users/UserController.php` |
| Page | `resources/js/Pages/Users/Index.vue` |
| Model | `app/Models/User.php` |
| Routes | `users.*` (`permission:users.manage`) |
| Test | `tests/Feature/UserManagementTest.php` |

## Checklist

- [x] CRUD + attach class/child/homeroom
- [x] `UserManagementTest` hijau

## Perintah

```bash
php artisan test --filter=UserManagementTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| GET `/users` | admin | daftar user |
| Buat siswa + attach kelas | admin | masuk rombel |
