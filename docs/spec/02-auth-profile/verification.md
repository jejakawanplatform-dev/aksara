# Verification — Auth & profil

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Routes | `routes/auth.php`; `profile.*` di `routes/web.php` |
| Controllers | `app/Http/Controllers/Auth/*`, `ProfileController.php` |
| Pages | `resources/js/Pages/Auth/*`, `Pages/Profile/Edit.vue` |
| Layout | `resources/js/Layouts/GuestLayout.vue` |
| Tests | `tests/Feature/Auth/*`, `ProfileTest.php` |

## Checklist

- [x] Login/register/logout via page Vue
- [x] Profil edit/update/destroy
- [x] Feature Auth + Profile hijau
- [x] FormRequest scaffold `authorize(): false` dihapus (tak terpakai)

## Perintah

```bash
php artisan test --filter=AuthenticationTest
php artisan test --filter=ProfileTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| Login | `naya@aksara.test` | `/dashboard` |
| Logout | — | ke login |
| Edit profil | guru | tersimpan |
