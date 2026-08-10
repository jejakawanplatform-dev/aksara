# Plan — Referensi kurikulum & sekolah

## Status

| Field | Isi |
|---|---|
| Kode | `06-references` |
| Status | selesai / aktif |
| Steering | `database-schema`, `business-rules` |

## Ringkasan

Hub `/references`: profil sekolah, operasional akademik, tahun ajaran, semester, rombel, mapel, CP/TP/ATP, plotting guru, enrol kelas, import/export multi-format.

## Tujuan

Master data kurikulum & operasional tersedia untuk rencana pembelajaran dan RBAC subject-scoped.

## Acceptance

- [x] CRUD entitas referensi
- [x] Import CP/TP & ATP; export Excel/Word/PDF
- [x] Guru scoped ke mapel diampu; admin penuh
- [x] `ReferenceCrudTest` + `ReferenceExportImportTest` hijau
