# Plan — Export PDF (Blade)

## Status

| Field | Isi |
|---|---|
| Kode | `14-exports-pdf` |
| Status | selesai / aktif |
| Steering | `coding-standards`, `api-contract` |

## Ringkasan

Satu-satunya permukaan Blade selain root Inertia: template PDF untuk rencana pembelajaran dan CP/TP/ATP. Dipicu dari controller export (bukan page Vue).

## Tujuan

Unduhan dokumen cetak tanpa membangun UI PDF di Vue.

## Acceptance

- [x] Empat template exports ada
- [x] Terhubung LearningPlan & Curriculum export controllers
- [x] Format non-PDF (xlsx/docx) tetap lewat service yang sama
- [x] Kop sekolah dari `system_settings` (partial bersama)
