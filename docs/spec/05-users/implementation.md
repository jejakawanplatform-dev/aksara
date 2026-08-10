# Implementation — Manajemen pengguna

## Artefak

| Area | Path |
|---|---|
| Controller | `app/Http/Controllers/Users/UserController.php` |
| Page | `resources/js/Pages/Users/Index.vue` |
| Model | `app/Models/User.php` (+ relasi classes/children/parents) |
| Routes | `users.*` |
| Test | `tests/Feature/UserManagementTest.php` |

## Alur

```text
/users → Users/Index.vue
  POST /users                  buat
  PUT  /users/{user}           update role/data
  DELETE /users/{user}         hapus
  POST attach-class / child / homeroom
```

## Otorisasi

Middleware `permission:users.manage` (default: admin).
