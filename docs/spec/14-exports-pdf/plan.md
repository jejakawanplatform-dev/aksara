# Plan — Sistem Ekspor Dokumen Multi-Format & PDF Resmi (Blade)

## Status

| Field | Isi |
|---|---|
| Kode | `14-exports-pdf` |
| Status | selesai / aktif |
| Steering | `coding-standards`, `api-contract`, ADR-014 |

## Ringkasan

Satu-satunya permukaan Blade selain root Inertia: sistem template cetak PDF resmi untuk RPP, Kurikulum (CP/TP/ATP), Materi Pembelajaran, dan Rekapitulasi Presensi. Dilengkapi ekspor multi-format (Excel `.xlsx` via `PhpOffice\PhpSpreadsheet`, Word `.docx`, dan Markdown `.md`) yang dikelola oleh controller dan service domain khusus.

## Tujuan

Menyediakan unduhan dan cetak dokumen resmi sekolah yang memenuhi standar tata naskah dinas (Kop Surat Sekolah dinamis, tanda tangan digital/fisik) serta format data spreadsheet tanpa membangun UI PDF di Vue (ADR-014).

## Acceptance

- [x] Enam template exports Blade cetak resmi (`plans-pdf`, `single-plan-pdf`, `cp-tp-pdf`, `atp-pdf`, `material-pdf`, `attendance-pdf`)
- [x] Terhubung dengan Export Controller: `LearningPlanExportController`, `CurriculumExportController`, `MaterialExportController`, `AttendanceExportController`
- [x] Format non-PDF (Excel `.xlsx`, Word `.docx`, Markdown `.md`) diproses lewat service domain murni
- [x] Kop sekolah dinamis membaca `system_settings` (`exports/partials/kop.blade.php`)
- [x] UI popup seragam menggunakan `ExportMenu.vue`
