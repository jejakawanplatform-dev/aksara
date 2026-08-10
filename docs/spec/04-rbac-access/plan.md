# Plan — Role & RBAC matrix

## Status

| Field | Isi |
|---|---|
| Kode | `04-rbac-access` |
| Status | selesai / aktif |
| Steering | `business-rules`, `api-contract`, decision ADR-003/007 |

## Ringkasan

Role tetap dari enum `UserRole`. Admin mengelola matrix permission×role di `/access`. Route fitur memakai middleware `permission:*`; identitas dashboard memakai `users.role`.

## Tujuan

Otorisasi fitur berbasis permission Spatie yang bisa diubah tanpa CRUD role bebas.

## Acceptance

- [x] Katalog permission di `PermissionCatalog`
- [x] Hub `/access` simpan + reset default
- [x] Permission wajib admin terkunci
- [x] `RbacMatrixTest` hijau
