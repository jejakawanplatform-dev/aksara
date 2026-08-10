# Implementation — Scaffold & boot Inertia

## Artefak

| Area | Path | Peran |
|---|---|---|
| Deps PHP | `composer.json` | `inertiajs/inertia-laravel`, `spatie/laravel-permission`, Breeze |
| Deps JS | `package.json` | `vue`, `@inertiajs/vue3`, `@tiptap/*`, `katex` |
| Vite | `vite.config.js` | input: `resources/css/app.css`, `resources/js/inertia-app.js` |
| Entry | `resources/js/inertia-app.js` | `createInertiaApp` + resolve `Pages/**/*.vue` |
| Root HTML | `resources/views/app.blade.php` | `@inertia` + `@vite` |
| Share | `app/Http/Middleware/HandleInertiaRequests.php` | `auth`, `nav`, `flash` |
| Config | `config/inertia.php` | initial page via script element |
| Role | `app/Enums/UserRole.php`, `app/Models/User.php` | identitas role |
| Seed | `database/seeders/DemoDataSeeder.php`, `app/Console/Commands/SeedDemo.php` | data workshop |
| Test | `tests/Feature/AksaraTest.php` | schema/role/seed |

## Alur boot

```text
HTTP → Laravel → HandleInertiaRequests → app.blade.php
     → inertia-app.js → Pages/{Name}.vue
```

## Catatan

- Blade non-root hanya untuk PDF export (spek 14).
- Stack UI resmi: ADR-010.
