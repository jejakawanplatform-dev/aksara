# Verification — Scaffold & boot Inertia

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Entry JS | `resources/js/inertia-app.js` |
| Root HTML | `resources/views/app.blade.php` |
| Vite | `vite.config.js` (input: CSS + `inertia-app.js`) |
| Share props | `app/Http/Middleware/HandleInertiaRequests.php` |
| Config | `config/inertia.php` |
| Role | `app/Enums/UserRole.php`, `app/Models/User.php` |
| Seed | `database/seeders/DemoDataSeeder.php` |
| Test | `tests/Feature/AksaraTest.php` |

## Checklist

- [x] Vite input hanya `resources/css/app.css` + `resources/js/inertia-app.js`
- [x] `createInertiaApp` resolve `Pages/**/*.vue`
- [x] Seed demo sesuai `docs/demo-users.md`
- [x] `AksaraTest` hijau

## Perintah

```bash
composer install
npm install && npm run build
php artisan migrate:fresh --seed
php artisan storage:link
php artisan test --filter=AksaraTest
```

## Uji manual

| Langkah | Harapan |
|---|---|
| Buka `/` | page `Welcome.vue` |
| `npm run build` | sukses |
