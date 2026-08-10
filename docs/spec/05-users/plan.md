# Plan — Manajemen pengguna

## Status

| Field | Isi |
|---|---|
| Kode | `05-users` |
| Status | selesai / aktif |
| Steering | `business-rules`, ADR-006 |

## Ringkasan

Admin mengelola akun (CRUD), role, enrol kelas siswa, relasi wali–anak, dan assignment wali kelas di `/users`.

## Tujuan

Operasional akun sekolah tanpa panel admin terpisah — satu shell Aksara.

## Acceptance

- [x] CRUD user + sync Spatie role
- [x] Attach/detach class & child; save homeroom
- [x] `UserManagementTest` hijau
