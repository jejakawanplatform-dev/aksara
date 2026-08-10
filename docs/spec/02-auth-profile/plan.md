# Plan — Auth & profil

## Status

| Field | Isi |
|---|---|
| Kode | `02-auth-profile` |
| Status | selesai / aktif |
| Steering | `api-contract`, `coding-standards` |

## Ringkasan

Autentikasi session Breeze (Inertia Vue): login, register, reset/confirm password, verifikasi email, logout, serta halaman profil.

## Tujuan

Pengguna bisa masuk/keluar dan mengelola profil dengan CSRF session — tanpa API token publik.

## Scope

**In scope:** routes `auth.php` + `profile.*`; Pages `Auth/*` + `Profile/Edit`; GuestLayout.  
**Out of scope:** SSO/OAuth, 2FA.

## Acceptance

- [x] Guest mengakses login/register
- [x] Auth mengakses profil
- [x] Feature test Auth + Profile hijau
