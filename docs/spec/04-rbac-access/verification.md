# Verification — Role & RBAC matrix

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Katalog | `app/Support/Access/PermissionCatalog.php` |
| Controller | `app/Http/Controllers/Access/AccessController.php` |
| Page | `resources/js/Pages/Access/Index.vue` |
| Seeder | `database/seeders/RolePermissionSeeder.php` |
| Routes | `access.index`, `access.save`, `access.reset-defaults` |
| Test | `tests/Feature/RbacMatrixTest.php` |

## Checklist

- [x] Middleware `permission:access.manage`
- [x] Matrix save + reset defaults
- [x] `RbacMatrixTest` hijau

## Perintah

```bash
php artisan test --filter=RbacMatrixTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| GET `/access` | admin | matrix tampil |
| GET `/access` | guru | 403 |
