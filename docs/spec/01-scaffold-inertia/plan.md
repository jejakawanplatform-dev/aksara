# Plan — Scaffold & boot Inertia

## Status

| Field | Isi |
|---|---|
| Kode | `01-scaffold-inertia` |
| Status | selesai / aktif |
| Steering | `product-brief`, `coding-standards`, `database-schema` |

## Ringkasan

Fondasi runtime Aksara: Laravel 13 + Inertia.js + Vue 3 + Vite, schema DB, seed demo, dan middleware share Inertia (`auth` / `nav` / `flash`).

## Tujuan

Project bisa di-install lokal, di-build, di-seed, dan merender page Vue lewat root Inertia.

## Scope

**In scope**

- Deps Composer/NPM (Inertia, Vue, Spatie Permission, TipTap, Breeze)
- Vite entry `inertia-app.js` + root `app.blade.php`
- `HandleInertiaRequests` share props
- Enum `UserRole`, model `User`, migrations inti
- `DemoDataSeeder` + `aksara:seed-demo`
- CI/tooling dasar (Pest, Pint, Larastan)

**Out of scope**

- Fitur domain (plans/materi/…) — spek 05+

## Acceptance

- [x] `composer install` + `npm run build`
- [x] `php artisan migrate:fresh --seed`
- [x] Page Vue resolve dari `Pages/**`
- [x] Shared props `auth`, `nav`, `flash` tersedia
