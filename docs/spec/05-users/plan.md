# Plan — Manajemen pengguna

## Status

| Field | Isi |
|---|---|
| Kode | `05-users` |
| Status | selesai / aktif |
| Steering | `business-rules`, ADR-006, ADR-015 |

## Ringkasan

Admin mengelola akun (CRUD), role, enrol kelas siswa, relasi wali–anak, assignment wali kelas di `/users`, serta onboarding data massal via ekspor/impor Excel (.xlsx) dan aksi massal penghapusan berpelindung integritas.

## Tujuan

Operasional akun sekolah yang efisien tanpa panel admin terpisah — satu shell Aksara, didukung pipa impor massal untuk percepatan onboarding tahun ajaran baru.

## Acceptance

- [x] CRUD user + sync Spatie role
- [x] Attach/detach class & child; save homeroom
- [x] Penghapusan massal pengguna ber-guardrail (`bulkDestroy`)
- [x] Ekspor & Impor data pengguna via Excel (.xlsx) dengan template resmi
- [x] Unduh rekap kredensial acak sementara
- [x] `UserManagementTest`, `UserBulkActionTest`, dan `UserExportImportTest` hijau
